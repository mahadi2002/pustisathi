<?php
declare(strict_types=1);

namespace App\Gateways;

use App\Core\Crypto;
use App\Core\Db;
use App\Core\Logger;
use App\Core\RateLimit;
use App\Repositories\UserRepo;
use App\Services\SubscriptionService;

/**
 * Active dev implementation. Generates a real OTP, logs it (never SMS'd) to
 * storage/logs/otp-*.log, and lets requestOtp/verifyOtp exercise the same
 * shape a real carrier-billing gateway would — bootstrap.php refuses to run
 * this class at all when APP_ENV=production, so it only ever runs locally.
 */
final class MockGateway implements SubscriptionGateway
{
    public function requestOtp(string $mobile): OtpRequestResult
    {
        $perNumberLimit = (int) config('app.otp.rate_per_hour', 3);
        $retry = RateLimit::hit('otp_request_mobile', 'mobile:' . Crypto::blindIndex($mobile), $perNumberLimit, 3600);
        if ($retry !== null) {
            return new OtpRequestResult(false, $retry, 'অনেকবার চেষ্টা হয়েছে। ' . RateLimit::humanWait($retry) . ' পর আবার চেষ্টা করুন।');
        }

        $length = (int) config('app.otp.length', 6);
        $otp    = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $ttl    = (int) config('app.otp.ttl', 300);

        Db::insert(
            'INSERT INTO otp_requests (mobile, otp_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))',
            [$mobile, password_hash($otp, PASSWORD_DEFAULT), $ttl]
        );

        // Dev-only channel — a real gateway would never have the plaintext code to log.
        Logger::channel('otp', "mobile_last4=" . substr($mobile, -4) . " otp={$otp} ttl={$ttl}s");

        return new OtpRequestResult(true);
    }

    public function verifyOtp(string $mobile, string $otp): SubscriptionResult
    {
        $maxAttempts = (int) config('app.otp.max_attempts', 5);

        $row = Db::first(
            'SELECT id, otp_hash, attempt_count FROM otp_requests
             WHERE mobile = ? AND consumed_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1',
            [$mobile]
        );

        if ($row === null) {
            return new SubscriptionResult(false, message: 'কোড-এর মেয়াদ শেষ হয়ে গেছে। আবার চেষ্টা করুন।');
        }

        if ((int) $row['attempt_count'] >= $maxAttempts) {
            return new SubscriptionResult(false, message: 'অনেকবার ভুল চেষ্টা হয়েছে। নতুন কোড চান।');
        }

        if (!password_verify($otp, (string) $row['otp_hash'])) {
            Db::exec('UPDATE otp_requests SET attempt_count = attempt_count + 1 WHERE id = ?', [$row['id']]);
            $left = $maxAttempts - ((int) $row['attempt_count'] + 1);
            return new SubscriptionResult(false, message: 'ভুল কোড। আরও ' . bn_num(max(0, $left)) . ' বার চেষ্টা করতে পারবেন।');
        }

        Db::exec('UPDATE otp_requests SET consumed_at = NOW() WHERE id = ?', [$row['id']]);

        $operator = self::detectOperator($mobile);
        [$userId, $isNew] = (new UserRepo())->findOrCreatePatient($mobile, $operator);
        $subscriptionId = SubscriptionService::activate($userId, $operator, 'mock');

        return new SubscriptionResult(true, $userId, $subscriptionId, $isNew);
    }

    public function checkStatus(string $subscriptionRef): SubscriptionStatus
    {
        $row = Db::first('SELECT status, next_charge_at FROM subscriptions WHERE id = ?', [(int) $subscriptionRef]);
        if ($row === null) {
            return new SubscriptionStatus(null);
        }
        return new SubscriptionStatus($row['status'], $row['next_charge_at']);
    }

    /** Mirrors what a real DCB webhook payload would trigger, for local testing without one. */
    public function handleWebhook(array $payload): WebhookResult
    {
        $subscriptionId = (int) ($payload['subscription_id'] ?? 0);
        $eventType      = (string) ($payload['event_type'] ?? 'webhook_unknown');
        $amount         = isset($payload['amount']) ? (float) $payload['amount'] : null;

        if ($subscriptionId <= 0) {
            return new WebhookResult(false);
        }

        Db::insert(
            'INSERT INTO billing_events (subscription_id, event_type, amount, raw_payload) VALUES (?, ?, ?, ?)',
            [$subscriptionId, $eventType, $amount, json_encode($payload, JSON_UNESCAPED_UNICODE)]
        );

        match ($eventType) {
            'charge_success' => SubscriptionService::recordChargeSuccess($subscriptionId),
            'charge_fail'    => SubscriptionService::recordChargeFailure($subscriptionId),
            'unsubscribe'    => Db::exec(
                "UPDATE subscriptions SET status = 'unsubscribed', unsubscribed_at = NOW() WHERE id = ?",
                [$subscriptionId]
            ),
            default => null,
        };

        return new WebhookResult(true, $eventType);
    }

    private static function detectOperator(string $mobile): string
    {
        $prefix = substr($mobile, 0, 3);
        return (string) (config('operators.prefixes')[$prefix] ?? 'unknown');
    }
}
