<?php
declare(strict_types=1);

namespace App\Gateways;

use App\Core\Db;
use App\Exceptions\OtpException;
use App\Repositories\UserRepo;
use App\Services\OtpService;
use App\Services\SubscriptionService;

/**
 * Active dev implementation. OTP mechanics live in OtpService, shared with
 * nutritionist registration; this class owns what happens once an OTP is
 * confirmed for the plain subscribe screen — a brand-new number becomes a
 * patient account, an existing number keeps its real role. Everyone except
 * admin gets an active subscription out of this; admin runs the platform
 * rather than pays for it. bootstrap.php refuses to run this class at all
 * when APP_ENV=production, so it only ever runs locally.
 */
final class MockGateway implements SubscriptionGateway
{
    public function requestOtp(string $mobile): OtpRequestResult
    {
        try {
            OtpService::request($mobile);
            return new OtpRequestResult(true);
        } catch (OtpException $e) {
            return new OtpRequestResult(false, message: $e->getMessage());
        }
    }

    public function verifyOtp(string $mobile, string $otp): SubscriptionResult
    {
        try {
            OtpService::verify($mobile, $otp);
        } catch (OtpException $e) {
            return new SubscriptionResult(false, message: $e->getMessage());
        }

        $operator = self::detectOperator($mobile);
        [$userId, $isNew] = (new UserRepo())->findOrCreatePatient($mobile, $operator);

        $role           = (string) Db::value('SELECT role FROM users WHERE id = ?', [$userId]);
        $subscriptionId = $role === 'admin' ? null : SubscriptionService::activate($userId, $operator, 'mock');

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

    public static function detectOperator(string $mobile): string
    {
        $prefix = substr($mobile, 0, 3);
        return (string) (config('operators.prefixes')[$prefix] ?? 'unknown');
    }
}
