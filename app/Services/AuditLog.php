<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Db;

/** A record of who did what, for billing and security actions specifically — not general activity tracking. */
final class AuditLog
{
    public static function record(?int $userId, string $action, array $details = [], ?string $ip = null): void
    {
        Db::insert(
            'INSERT INTO audit_log (user_id, action, details_json, ip_hash) VALUES (?, ?, ?, ?)',
            [
                $userId,
                $action,
                $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE),
                $ip === null ? null : Crypto::blindIndex('ip:' . $ip),
            ]
        );
    }
}
