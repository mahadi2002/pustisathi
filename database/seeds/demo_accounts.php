<?php
declare(strict_types=1);

/**
 * Local-only test accounts for logging in without going through the full
 * signup flow every time. Every role signs in the same way here — mobile +
 * OTP, dev code 123456 — so these differ from a real account only in
 * already having a role/status/subscription set up in advance.
 * Run via `php database/migrate.php --seed`.
 */

use App\Core\Crypto;
use App\Core\Db;

if (config('app.env') === 'production') {
    out('   skipped — demo accounts are never seeded in production');
    return;
}

$adminMobile = '01899999999';
if (Db::value('SELECT 1 FROM users WHERE mobile_hash = ?', [Crypto::blindIndex('mobile:' . $adminMobile)]) === null) {
    Db::insert(
        'INSERT INTO users (role, mobile, mobile_hash, operator) VALUES (?, ?, ?, ?)',
        ['admin', Crypto::encrypt($adminMobile), Crypto::blindIndex('mobile:' . $adminMobile), 'robi']
    );
    out("   admin: {$adminMobile}, OTP code 123456 — no subscription needed");
}

$nutriMobile = '01812345678';
$nutriId     = Db::value('SELECT id FROM users WHERE mobile_hash = ?', [Crypto::blindIndex('mobile:' . $nutriMobile)]);

if ($nutriId === null) {
    $nutriId = Db::insert(
        'INSERT INTO users (role, mobile, mobile_hash, operator, nutritionist_status, nutritionist_creds)
         VALUES (?, ?, ?, ?, ?, ?)',
        [
            'nutritionist',
            Crypto::encrypt($nutriMobile),
            Crypto::blindIndex('mobile:' . $nutriMobile),
            'robi',
            'approved',
            'Demo seed account — BSc in Nutrition & Food Science (sample credential text)',
        ]
    );
    Db::insert(
        "INSERT INTO subscriptions (user_id, status, operator, gateway, activated_at, next_charge_at)
         VALUES (?, 'active', 'robi', 'mock', NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY))",
        [$nutriId]
    );
    out("   nutritionist (pre-approved + pre-subscribed): {$nutriMobile}, OTP code 123456");
}
