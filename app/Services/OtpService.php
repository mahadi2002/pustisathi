<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Db;
use App\Core\Logger;
use App\Core\RateLimit;
use App\Exceptions\OtpException;

/**
 * The actual OTP mechanics, shared by every flow that needs to prove someone
 * controls a phone number — the patient subscribe funnel and the
 * nutritionist billing-link both go through this instead of duplicating it.
 *
 * DEV_CODE only ever verifies when the mock gateway is active (bootstrap.php
 * already refuses to run mock in production), so this is a dev/demo
 * convenience, not a backdoor into anything real.
 */
final class OtpService
{
    public const DEV_CODE = '123456';

    /** @throws OtpException on throttle */
    public static function request(string $mobile): void
    {
        $perNumberLimit = (int) config('app.otp.rate_per_hour', 3);
        $retry = RateLimit::hit('otp_request_mobile', 'mobile:' . Crypto::blindIndex($mobile), $perNumberLimit, 3600);
        if ($retry !== null) {
            throw new OtpException(
                'rate_limited',
                'অনেকবার চেষ্টা হয়েছে। ' . RateLimit::humanWait($retry) . ' পর আবার চেষ্টা করুন।'
            );
        }

        $length = (int) config('app.otp.length', 6);
        $otp    = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $ttl    = (int) config('app.otp.ttl', 300);

        Db::insert(
            'INSERT INTO otp_requests (mobile_hash, otp_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))',
            [Crypto::blindIndex('mobile:' . $mobile), password_hash($otp, PASSWORD_DEFAULT), $ttl]
        );

        // Dev-only channel — a real gateway would never hand us the plaintext code to log.
        Logger::channel('otp', "mobile_last4=" . substr($mobile, -4) . " otp={$otp} ttl={$ttl}s");
    }

    /** @throws OtpException on expiry, wrong code, or too many attempts */
    public static function verify(string $mobile, string $code): void
    {
        $maxAttempts = (int) config('app.otp.max_attempts', 5);

        $row = Db::first(
            'SELECT id, otp_hash, attempt_count FROM otp_requests
             WHERE mobile_hash = ? AND consumed_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1',
            [Crypto::blindIndex('mobile:' . $mobile)]
        );

        if ($row === null) {
            throw new OtpException('expired', 'কোড-এর মেয়াদ শেষ হয়ে গেছে। আবার চেষ্টা করুন।');
        }

        if ((int) $row['attempt_count'] >= $maxAttempts) {
            throw new OtpException('too_many', 'অনেকবার ভুল চেষ্টা হয়েছে। নতুন কোড চান।');
        }

        $isDevCode = config('gateway.driver') === 'mock' && $code === self::DEV_CODE;

        if (!$isDevCode && !password_verify($code, (string) $row['otp_hash'])) {
            Db::exec('UPDATE otp_requests SET attempt_count = attempt_count + 1 WHERE id = ?', [$row['id']]);
            $left = $maxAttempts - ((int) $row['attempt_count'] + 1);
            throw new OtpException(
                'invalid',
                'ভুল কোড। আরও ' . bn_num(max(0, $left)) . ' বার চেষ্টা করতে পারবেন।',
                max(0, $left)
            );
        }

        Db::exec('UPDATE otp_requests SET consumed_at = NOW() WHERE id = ?', [$row['id']]);
    }
}
