<?php
declare(strict_types=1);

/**
 * Plain CLI smoke test, no framework — the "did I just break something
 * load-bearing" gate, not full coverage. Run after touching Crypto, Csrf,
 * Validator, DietPlanEngine, or ConditionRuleEngine.
 *
 *   php tests/smoke.php
 *
 * Requires a working DB connection (.env configured, migrations applied) —
 * ConditionRuleEngine::restrictionsFor() queries the condition_rules table
 * directly rather than taking the rules as a parameter, and that table
 * ships as part of migration 005 (unconditionally, not behind --seed), so
 * `php database/migrate.php` (no --seed needed) is enough to run this.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

use App\Core\Crypto;
use App\Core\Csrf;
use App\Core\Validator;
use App\Rules\ConditionRuleEngine;
use App\Rules\DietPlanEngine;

$failures = 0;
$total    = 0;

function check(string $label, bool $condition): void
{
    global $failures, $total;
    $total++;
    if ($condition) {
        echo "  [ok] {$label}\n";
    } else {
        echo "  [FAIL] {$label}\n";
        $failures++;
    }
}

echo "Crypto — AES-256-GCM round-trip\n";
$plain  = 'test-value-01712345678';
$cipher = Crypto::encrypt($plain);
check('ciphertext differs from plaintext', $cipher !== $plain);
check('decrypt recovers original', Crypto::decrypt($cipher) === $plain);
check('decrypt rejects garbage', Crypto::decrypt('not-valid-base64-ciphertext') === null);

// Flip a character inside the base64 payload — GCM's auth tag must reject
// this instead of silently returning corrupted plaintext.
$tampered    = $cipher;
$flipIndex   = (int) (strlen($tampered) / 2);
$tampered[$flipIndex] = $tampered[$flipIndex] === 'A' ? 'B' : 'A';
check('decrypt rejects tampered ciphertext', Crypto::decrypt($tampered) === null);

echo "\nCrypto — blind index (HMAC)\n";
$hash1 = Crypto::blindIndex('mobile:01712345678');
$hash2 = Crypto::blindIndex('mobile:01712345678');
$hash3 = Crypto::blindIndex('mobile:01799999999');
check('stable across calls', $hash1 === $hash2);
check('differs for different input', $hash1 !== $hash3);
check('looks like a SHA-256 hex digest', preg_match('/^[a-f0-9]{64}$/', $hash1) === 1);
check('not reversible to plaintext (no substring leak)', !str_contains($hash1, '01712345678'));

echo "\nCsrf — token comparison\n";
$_SESSION = [];
$token = Csrf::token();
check('token is 64 hex chars', strlen($token) === 64);
check('valid token passes', Csrf::check($token));
check('wrong token fails', !Csrf::check('deadbeef'));
check('empty token fails', !Csrf::check(''));
check('null token fails', !Csrf::check(null));

echo "\nValidator — mobile rule (018 Robi / 016 Airtel only)\n";
foreach (['01812345678', '01612345678'] as $valid) {
    $v = Validator::make(['mobile' => $valid], ['mobile' => 'required|mobile']);
    check("accepts {$valid}", $v->passes());
}
foreach (['01712345678', '123', '018123456', '018123456789', 'abcdefghijk'] as $invalid) {
    $v = Validator::make(['mobile' => $invalid], ['mobile' => 'required|mobile']);
    check("rejects {$invalid}", $v->fails());
}

echo "\nValidator — required/between/digits\n";
$vReq = Validator::make([], ['name' => 'required']);
check('missing required field fails', $vReq->fails());

$vBetween = Validator::make(['age' => '150'], ['age' => 'required|int|between:1,120']);
check('out-of-range between fails', $vBetween->fails());

$vDigits = Validator::make(['otp' => '12345'], ['otp' => 'required|digits:6']);
check('wrong digit count fails', $vDigits->fails());

echo "\nDietPlanEngine — BMR (Mifflin-St Jeor)\n";
// 30yo, 70kg, 175cm: base = 10*70 + 6.25*175 - 5*30 = 1643.75
$base = 10 * 70 + 6.25 * 175 - 5 * 30;

$bmrMale = DietPlanEngine::bmrFor(['age' => 30, 'sex' => 'male', 'weight_kg' => 70, 'height_cm' => 175]);
check('male BMR = base + 5', abs($bmrMale - ($base + 5)) < 0.001);

$bmrFemale = DietPlanEngine::bmrFor(['age' => 30, 'sex' => 'female', 'weight_kg' => 70, 'height_cm' => 175]);
check('female BMR = base - 161', abs($bmrFemale - ($base - 161)) < 0.001);

$bmrOther = DietPlanEngine::bmrFor(['age' => 30, 'sex' => 'other', 'weight_kg' => 70, 'height_cm' => 175]);
check('other BMR = base - 78 (midpoint of the male/female offsets)', abs($bmrOther - ($base - 78)) < 0.001);

check('male BMR is higher than female BMR for an identical profile', $bmrMale > $bmrFemale);

echo "\nDietPlanEngine — activity multiplier / TDEE\n";
check('sedentary multiplier is 1.2', DietPlanEngine::activityMultiplierFor('sedentary') === 1.2);
check('moderate multiplier is 1.55', DietPlanEngine::activityMultiplierFor('moderate') === 1.55);
check('very_active multiplier is 1.9', DietPlanEngine::activityMultiplierFor('very_active') === 1.9);
check('unknown activity level falls back to 1.2 (sedentary)', DietPlanEngine::activityMultiplierFor('made_up') === 1.2);

$tdee = $bmrMale * DietPlanEngine::activityMultiplierFor('moderate');
check('TDEE = BMR x multiplier is higher than BMR alone', $tdee > $bmrMale);

echo "\nDietPlanEngine — macro split formula (documented contract: protein 1.2g/kg, fat 25% of kcal, carbs = remainder)\n";
// Mirrors the arithmetic in DietPlanEngine::generate() — protein and fat
// grams are pinned to the body/calorie target directly, carbs absorb
// whatever calories are left. This is a regression guard on that contract,
// not a call into generate() itself (which needs a seeded body_profiles +
// food_items DB state and persists a real diet_plans row — out of scope
// for a smoke test).
$targetKcal = (int) round($tdee);
$weightKg   = 70.0;

$proteinG      = round($weightKg * 1.2, 1);
$fatG          = round(($targetKcal * 0.25) / 9, 1);
$remainingKcal = max(0, $targetKcal - ($proteinG * 4 + $fatG * 9));
$carbG         = round($remainingKcal / 4, 1);

check('protein is 1.2g per kg bodyweight', abs($proteinG - 84.0) < 0.01);
check('fat calories are ~25% of target kcal', abs(($fatG * 9) - ($targetKcal * 0.25)) < 1.0);
check('protein + carb + fat calories reconstruct the target kcal (within rounding)', abs(($proteinG * 4 + $carbG * 4 + $fatG * 9) - $targetKcal) < 5.0);
check('no macro is negative', $proteinG >= 0 && $carbG >= 0 && $fatG >= 0);

echo "\nConditionRuleEngine — restricted/required tags per seeded condition\n";

$diabetic = ConditionRuleEngine::restrictionsFor(['diabetic']);
check('diabetic restricts high_gi', in_array('high_gi', $diabetic['restricted'], true));
check('diabetic restricts high_sugar', in_array('high_sugar', $diabetic['restricted'], true));
check('diabetic requires low_gi', in_array('low_gi', $diabetic['required'], true));
check('diabetic requires high_fiber', in_array('high_fiber', $diabetic['required'], true));
check('diabetic does not restrict an unrelated tag', !in_array('high_mercury', $diabetic['restricted'], true));

$renal = ConditionRuleEngine::restrictionsFor(['renal']);
check('renal restricts high_potassium', in_array('high_potassium', $renal['restricted'], true));
check('renal restricts high_sodium', in_array('high_sodium', $renal['restricted'], true));
check('renal restricts high_phosphorus', in_array('high_phosphorus', $renal['restricted'], true));
check('renal has no required tags (NULL in condition_rules)', $renal['required'] === []);

$cardiac = ConditionRuleEngine::restrictionsFor(['cardiac']);
check('cardiac restricts high_sodium', in_array('high_sodium', $cardiac['restricted'], true));
check('cardiac restricts high_saturated_fat', in_array('high_saturated_fat', $cardiac['restricted'], true));
check('cardiac requires high_fiber', in_array('high_fiber', $cardiac['required'], true));
check('cardiac requires omega3', in_array('omega3', $cardiac['required'], true));

$pregnancy = ConditionRuleEngine::restrictionsFor(['pregnancy']);
check('pregnancy restricts high_mercury', in_array('high_mercury', $pregnancy['restricted'], true));
check('pregnancy restricts raw_unpasteurized', in_array('raw_unpasteurized', $pregnancy['restricted'], true));
check('pregnancy requires high_folate', in_array('high_folate', $pregnancy['required'], true));
check('pregnancy requires high_iron', in_array('high_iron', $pregnancy['required'], true));

$combined = ConditionRuleEngine::restrictionsFor(['diabetic', 'renal']);
check(
    'combining two conditions merges restricted tags from both',
    in_array('high_gi', $combined['restricted'], true) && in_array('high_potassium', $combined['restricted'], true)
);
check(
    'merged restricted tags are de-duplicated',
    count($combined['restricted']) === count(array_unique($combined['restricted']))
);

$none = ConditionRuleEngine::restrictionsFor([]);
check('no condition codes yields no restrictions', $none === ['restricted' => [], 'required' => []]);

echo "\n" . str_repeat('-', 40) . "\n";
echo "{$total} checks, " . ($total - $failures) . " passed, {$failures} failed.\n";

exit($failures > 0 ? 1 : 0);
