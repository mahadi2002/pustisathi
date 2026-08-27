<?php
declare(strict_types=1);

namespace App\Rules;

use App\Core\Crypto;
use App\Core\Db;
use RuntimeException;

/**
 * Turns a body profile into a full day's meals. Deterministic on purpose —
 * same profile in, same plan out — so a support conversation about "why
 * did it pick this" has a real answer instead of "it was random that day."
 */
final class DietPlanEngine
{
    private const ACTIVITY_MULTIPLIERS = [
        'sedentary'   => 1.2,
        'light'       => 1.375,
        'moderate'    => 1.55,
        'active'      => 1.725,
        'very_active' => 1.9,
    ];

    private const MEAL_KCAL_SHARE = [
        'breakfast' => 0.25,
        'lunch'     => 0.35,
        'dinner'    => 0.30,
        'snack'     => 0.10,
    ];

    /**
     * Each slot lists the category groups it needs filled, with the share of
     * that meal's calories each one should carry. Splitting calories evenly
     * per item instead of by role would ask someone to eat a kilogram of
     * bottle gourd to hit a vegetable's "equal share" — staples carry more
     * of the calorie budget, vegetables carry less and more volume.
     */
    private const MEAL_TEMPLATE = [
        'breakfast' => [
            ['categories' => ['grain'], 'share' => 0.45],
            ['categories' => ['dairy', 'legume'], 'share' => 0.35],
            ['categories' => ['fruit'], 'share' => 0.20],
        ],
        'lunch' => [
            ['categories' => ['grain'], 'share' => 0.40],
            ['categories' => ['fish', 'meat', 'legume'], 'share' => 0.35],
            ['categories' => ['vegetable'], 'share' => 0.15],
            ['categories' => ['vegetable'], 'share' => 0.10],
        ],
        'dinner' => [
            ['categories' => ['grain'], 'share' => 0.40],
            ['categories' => ['fish', 'meat', 'legume'], 'share' => 0.35],
            ['categories' => ['vegetable'], 'share' => 0.15],
            ['categories' => ['vegetable'], 'share' => 0.10],
        ],
        'snack' => [
            ['categories' => ['fruit', 'dairy'], 'share' => 1.0],
        ],
    ];

    /** A single serving stays within a plausible real-world range regardless of what the math says. */
    private const MIN_PORTION_GRAMS = 20.0;
    private const MAX_PORTION_GRAMS = 400.0;

    /** Exposed so the dashboard can show BMR/TDEE as their own figures, not just the final target. */
    public static function bmrFor(array $profile): float
    {
        return self::bmr((int) $profile['age'], (string) $profile['sex'], (float) $profile['weight_kg'], (float) $profile['height_cm']);
    }

    public static function activityMultiplierFor(string $activityLevel): float
    {
        return self::ACTIVITY_MULTIPLIERS[$activityLevel] ?? 1.2;
    }

    /** The active plan plus its meal lines, grouped by slot and joined to food names/macros. */
    public static function currentPlan(int $userId): ?array
    {
        $plan = Db::first(
            "SELECT * FROM diet_plans WHERE user_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1",
            [$userId]
        );
        if ($plan === null) {
            return null;
        }

        $rows = Db::all(
            "SELECT pm.meal_slot, pm.portion_grams, pm.sort_order,
                    f.name_bn, f.name_en, f.per_100g_kcal, f.per_100g_protein_g, f.per_100g_carb_g, f.per_100g_fat_g
             FROM plan_meals pm JOIN food_items f ON f.id = pm.food_id
             WHERE pm.plan_id = ?
             ORDER BY FIELD(pm.meal_slot, 'breakfast','lunch','dinner','snack'), pm.sort_order",
            [$plan['id']]
        );

        $bySlot = ['breakfast' => [], 'lunch' => [], 'dinner' => [], 'snack' => []];
        foreach ($rows as $row) {
            $bySlot[$row['meal_slot']][] = $row;
        }

        $plan['meals'] = $bySlot;
        return $plan;
    }

    public static function generate(int $userId, int $createdBy, string $source = 'self_generated'): int
    {
        $profile = Db::first('SELECT * FROM body_profiles WHERE user_id = ?', [$userId]);
        if ($profile === null) {
            throw new RuntimeException('No body profile to generate a plan from.');
        }

        $bmr        = self::bmr((int) $profile['age'], (string) $profile['sex'], (float) $profile['weight_kg'], (float) $profile['height_cm']);
        $multiplier = self::ACTIVITY_MULTIPLIERS[$profile['activity_level']] ?? 1.2;
        $targetKcal = (int) round($bmr * $multiplier);

        $proteinG      = round((float) $profile['weight_kg'] * 1.2, 1);
        $fatG          = round(($targetKcal * 0.25) / 9, 1);
        $remainingKcal = max(0, $targetKcal - ($proteinG * 4 + $fatG * 9));
        $carbG         = round($remainingKcal / 4, 1);

        $conditionCodes = self::decryptConditions($profile['medical_flags_enc']);
        $restrictions   = ConditionRuleEngine::restrictionsFor($conditionCodes);

        $budgetTiers = match ($profile['budget_tier']) {
            'low'   => ['low'],
            'mid'   => ['low', 'mid'],
            'high'  => ['low', 'mid', 'high'],
            default => ['low', 'mid', 'high'],
        };

        $regionId   = $profile['region_id'] !== null ? (int) $profile['region_id'] : null;
        $candidates = self::eligibleFoods($regionId, $budgetTiers, $restrictions['restricted']);

        $usedIds = [];
        $meals   = [];
        foreach (self::MEAL_KCAL_SHARE as $slot => $share) {
            $meals[$slot] = self::assembleMeal($slot, $targetKcal * $share, $candidates, $restrictions['required'], $usedIds);
        }

        return self::persist(
            $userId,
            $createdBy,
            $source,
            $targetKcal,
            $proteinG,
            $carbG,
            $fatG,
            (string) $profile['budget_tier'],
            $conditionCodes,
            $meals
        );
    }

    private static function bmr(int $age, string $sex, float $weightKg, float $heightCm): float
    {
        $base = 10 * $weightKg + 6.25 * $heightCm - 5 * $age;
        return match ($sex) {
            'male'   => $base + 5,
            'female' => $base - 161,
            // No separate clinical formula for a non-binary sex marker — split
            // the difference between the two offsets rather than picking one.
            default  => $base - 78,
        };
    }

    /** @return string[] */
    private static function decryptConditions(?string $encFlags): array
    {
        if ($encFlags === null) {
            return [];
        }
        $json = Crypto::decrypt($encFlags);
        if ($json === null) {
            return [];
        }
        $flags = json_decode($json, true);
        return is_array($flags) ? array_values(array_filter($flags, 'is_string')) : [];
    }

    /**
     * @param string[] $budgetTiers
     * @param string[] $restrictedTags
     * @return array<int,array<string,mixed>>
     */
    private static function eligibleFoods(?int $regionId, array $budgetTiers, array $restrictedTags): array
    {
        $tierPlaceholders = Db::placeholders($budgetTiers);
        $regionJoin       = '';
        $regionParams     = [];

        if ($regionId !== null) {
            $regionJoin     = 'JOIN food_availability fa ON fa.food_id = f.id AND fa.region_id = ?';
            $regionParams[] = $regionId;
        }

        // Params must be in the same left-to-right order as their placeholders:
        // the region join comes before the cost_tier WHERE in the SQL text.
        $rows = Db::all(
            "SELECT DISTINCT f.* FROM food_items f $regionJoin WHERE f.cost_tier IN ($tierPlaceholders)",
            [...$regionParams, ...$budgetTiers]
        );

        $eligible = [];
        foreach ($rows as $row) {
            $tags = json_decode((string) ($row['tags'] ?? '[]'), true) ?: [];
            if (array_intersect($tags, $restrictedTags) !== []) {
                continue;
            }
            $row['tags_decoded'] = $tags;
            $eligible[]          = $row;
        }
        return $eligible;
    }

    /**
     * @param array<int,array<string,mixed>> $candidates
     * @param string[] $requiredTags
     * @param int[] $usedIds
     * @return array<int,array{food_id:int,portion_grams:float,sort_order:int}>
     */
    private static function assembleMeal(string $slot, float $mealKcal, array $candidates, array $requiredTags, array &$usedIds): array
    {
        $lines = [];

        foreach (self::MEAL_TEMPLATE[$slot] as $sortOrder => $entry) {
            $pool = array_values(array_filter(
                $candidates,
                static fn(array $f): bool => in_array($f['category'], $entry['categories'], true)
            ));
            if ($pool === []) {
                continue;
            }

            usort($pool, static function (array $a, array $b) use ($requiredTags, $usedIds): int {
                $aRequired = array_intersect($a['tags_decoded'], $requiredTags) !== [] ? 1 : 0;
                $bRequired = array_intersect($b['tags_decoded'], $requiredTags) !== [] ? 1 : 0;
                if ($aRequired !== $bRequired) {
                    return $bRequired <=> $aRequired;
                }
                $aUsed = in_array($a['id'], $usedIds, true) ? 1 : 0;
                $bUsed = in_array($b['id'], $usedIds, true) ? 1 : 0;
                if ($aUsed !== $bUsed) {
                    return $aUsed <=> $bUsed;
                }
                return $a['id'] <=> $b['id'];
            });

            $food = $pool[0];
            if ($requiredTags !== [] && array_intersect($food['tags_decoded'], $requiredTags) === []) {
                // The sort above puts any required-tag match first, so if the
                // winner has none, nothing in this slot's pool did either —
                // the condition's nutrient requirement went unmet here. The
                // plan still gets built (no food in stock beats no plan at
                // all), but this can't be allowed to vanish silently.
                \App\Core\Logger::warning('diet_plan.required_tag_unmet', [
                    'slot'          => $slot,
                    'categories'    => $entry['categories'],
                    'required_tags' => $requiredTags,
                ]);
            }
            $usedIds[]   = (int) $food['id'];
            $kcalForItem = $mealKcal * $entry['share'];
            $kcalPer100g = max(0.1, (float) $food['per_100g_kcal']);
            $grams       = max(self::MIN_PORTION_GRAMS, min(self::MAX_PORTION_GRAMS, ($kcalForItem / $kcalPer100g) * 100));

            $lines[] = [
                'food_id'       => (int) $food['id'],
                'portion_grams' => round($grams, 1),
                'sort_order'    => $sortOrder,
            ];
        }

        return $lines;
    }

    /** @param string[] $conditionCodes */
    private static function persist(
        int $userId,
        int $createdBy,
        string $source,
        int $targetKcal,
        float $proteinG,
        float $carbG,
        float $fatG,
        string $budgetTier,
        array $conditionCodes,
        array $meals
    ): int {
        return Db::transaction(static function () use (
            $userId, $createdBy, $source, $targetKcal, $proteinG, $carbG, $fatG, $budgetTier, $conditionCodes, $meals
        ): int {
            Db::exec("UPDATE diet_plans SET status = 'archived' WHERE user_id = ? AND status = 'active'", [$userId]);

            $planId = Db::insert(
                "INSERT INTO diet_plans (user_id, created_by, source, target_kcal, macro_targets_json, budget_tier, condition_codes, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'active')",
                [
                    $userId,
                    $createdBy,
                    $source,
                    $targetKcal,
                    json_encode(['protein_g' => $proteinG, 'carb_g' => $carbG, 'fat_g' => $fatG], JSON_UNESCAPED_UNICODE),
                    $budgetTier,
                    json_encode($conditionCodes, JSON_UNESCAPED_UNICODE),
                ]
            );

            $placeholders = [];
            $params       = [];
            foreach ($meals as $slot => $lines) {
                foreach ($lines as $line) {
                    $placeholders[] = '(?, ?, ?, ?, ?)';
                    array_push($params, $planId, $slot, $line['food_id'], $line['portion_grams'], $line['sort_order']);
                }
            }
            if ($placeholders !== []) {
                Db::exec(
                    'INSERT INTO plan_meals (plan_id, meal_slot, food_id, portion_grams, sort_order) VALUES '
                        . implode(', ', $placeholders),
                    $params
                );
            }

            return $planId;
        });
    }
}
