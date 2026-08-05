<?php
declare(strict_types=1);

/**
 * Local-only test accounts for logging in without going through the full
 * signup flow every time. Run via `php database/migrate.php --seed`.
 */

use App\Core\Crypto;
use App\Core\Db;

if (config('app.env') === 'production') {
    out('   skipped — demo accounts are never seeded in production');
    return;
}

const DEMO_PASSWORD = 'ChangeMe123!';

$adminEmail = 'admin@pustisathi.test';
if (Db::value('SELECT 1 FROM users WHERE email = ?', [$adminEmail]) === null) {
    Db::insert(
        'INSERT INTO users (role, email, password_hash) VALUES (?, ?, ?)',
        ['admin', $adminEmail, password_hash(DEMO_PASSWORD, PASSWORD_BCRYPT, ['cost' => 12])]
    );
    out('   admin: admin@pustisathi.test / ' . DEMO_PASSWORD);
}

$nutriEmail  = 'nutritionist@pustisathi.test';
$nutriMobile = '01812345678';
$nutriId     = Db::value('SELECT id FROM users WHERE email = ?', [$nutriEmail]);

if ($nutriId === null) {
    $nutriId = Db::insert(
        'INSERT INTO users (role, email, password_hash, mobile, mobile_hash, operator, nutritionist_status, nutritionist_creds)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            'nutritionist',
            $nutriEmail,
            password_hash(DEMO_PASSWORD, PASSWORD_BCRYPT, ['cost' => 12]),
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
    out('   nutritionist (pre-approved + pre-subscribed): nutritionist@pustisathi.test / ' . DEMO_PASSWORD);
}
