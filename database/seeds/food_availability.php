<?php
declare(strict_types=1);

/**
 * Marks every seeded food as year-round available in every seeded region.
 * Real seasonal/regional variation needs per-food field data nobody's
 * collected yet — this is a placeholder so the diet engine's region filter
 * has something to match against instead of finding nothing everywhere.
 *
 * MySQL, not Postgres — `ON CONFLICT DO NOTHING` is Postgres/SQLite syntax
 * and threw a SQL syntax error against this app's mysql: PDO DSN
 * (App\Core\Db). INSERT IGNORE is the MySQL equivalent, and dedupes exactly
 * as intended here since food_availability's PRIMARY KEY is (food_id,
 * region_id) — a repeat --seed run is a true no-op, same as the original
 * ON CONFLICT DO NOTHING was meant to be.
 */

use App\Core\Db;

$foodIds   = array_column(Db::all('SELECT id FROM food_items'), 'id');
$regionIds = array_column(Db::all('SELECT id FROM regions'), 'id');

$inserted = 0;
foreach ($foodIds as $foodId) {
    foreach ($regionIds as $regionId) {
        Db::exec(
            'INSERT IGNORE INTO food_availability (food_id, region_id, seasonal) VALUES (?, ?, ?)',
            [$foodId, $regionId, 'year_round']
        );
        $inserted++;
    }
}

out("   linked {$inserted} food/region availability rows");
