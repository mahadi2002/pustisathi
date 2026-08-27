<?php
declare(strict_types=1);

/**
 * Local-only test accounts for logging in without going through the full
 * signup flow every time. Every role signs in the same way here — email +
 * password — so these differ from a real account only in already having a
 * role/status set up in advance.
 * Run via `php database/migrate.php --seed`.
 */

use App\Core\Db;

if (config('app.env') === 'production') {
    out('   skipped — demo accounts are never seeded in production');
    return;
}

$adminEmail    = 'admin@pustisathi.test';
$adminPassword = 'AdminDemo#2026';
if (Db::value('SELECT 1 FROM users WHERE email = ?', [$adminEmail]) === null) {
    Db::insert(
        "INSERT INTO users (role, email, password_hash) VALUES ('admin', ?, ?)",
        [$adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT)]
    );
    out("   admin: {$adminEmail} / {$adminPassword}");
}

$nutriEmail    = 'nutritionist@pustisathi.test';
$nutriPassword = 'NutriDemo#2026';
if (Db::value('SELECT 1 FROM users WHERE email = ?', [$nutriEmail]) === null) {
    Db::insert(
        "INSERT INTO users (role, email, password_hash, nutritionist_status, nutritionist_creds)
         VALUES ('nutritionist', ?, ?, 'approved', ?)",
        [
            $nutriEmail,
            password_hash($nutriPassword, PASSWORD_DEFAULT),
            'Demo seed account — BSc in Nutrition & Food Science (sample credential text)',
        ]
    );
    out("   nutritionist (pre-approved): {$nutriEmail} / {$nutriPassword}");
}
