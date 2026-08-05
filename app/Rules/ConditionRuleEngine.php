<?php
declare(strict_types=1);

namespace App\Rules;

use App\Core\Db;

/** Turns a patient's condition codes into the tag restrictions the diet engine actually applies. */
final class ConditionRuleEngine
{
    /**
     * @param string[] $conditionCodes
     * @return array{restricted: string[], required: string[]}
     */
    public static function restrictionsFor(array $conditionCodes): array
    {
        if ($conditionCodes === []) {
            return ['restricted' => [], 'required' => []];
        }

        $placeholders = Db::placeholders($conditionCodes);
        $rows = Db::all(
            "SELECT restricted_tags, required_tags FROM condition_rules WHERE condition_code IN ($placeholders)",
            $conditionCodes
        );

        $restricted = [];
        $required   = [];

        foreach ($rows as $row) {
            $restricted = array_merge($restricted, json_decode((string) $row['restricted_tags'], true) ?: []);
            $required   = array_merge($required, json_decode((string) $row['required_tags'], true) ?: []);
        }

        return [
            'restricted' => array_values(array_unique($restricted)),
            'required'   => array_values(array_unique($required)),
        ];
    }
}
