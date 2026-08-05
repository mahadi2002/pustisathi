<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

final class SubscriptionService
{
    /**
     * Always re-read from the DB — never cache this in the session.
     * Grace still counts as access: it exists so one missed charge doesn't
     * cut someone off mid-plan while the next retry is still pending.
     */
    public static function hasAccess(int $userId): bool
    {
        return (bool) Db::value(
            "SELECT 1 FROM subscriptions WHERE user_id = ? AND status IN ('active','grace') LIMIT 1",
            [$userId]
        );
    }

    public static function current(int $userId): ?array
    {
        return Db::first(
            'SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1',
            [$userId]
        );
    }

    /** Create a fresh active subscription, or reactivate the most recent one. */
    public static function activate(int $userId, string $operator, string $gateway, ?string $gatewayRef = null): int
    {
        $existing = self::current($userId);

        if ($existing !== null && !in_array($existing['status'], ['active', 'grace'], true)) {
            Db::exec(
                "UPDATE subscriptions SET status = 'active', activated_at = NOW(), unsubscribed_at = NULL,
                 next_charge_at = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = ?",
                [$existing['id']]
            );
            AuditLog::record($userId, 'subscription_activated', ['subscription_id' => $existing['id']]);
            return (int) $existing['id'];
        }

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $id = Db::insert(
            'INSERT INTO subscriptions (user_id, status, operator, gateway, gateway_ref, activated_at, next_charge_at)
             VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY))',
            [$userId, 'active', $operator, $gateway, $gatewayRef]
        );
        AuditLog::record($userId, 'subscription_activated', ['subscription_id' => $id]);
        return $id;
    }

    /**
     * A missed charge gets one retry cycle before access actually goes away:
     * active -> grace on the first miss, grace -> expired if the retry also
     * fails. A subscription that never had a successful first charge (still
     * 'pending') just goes to 'failed' instead — there's no plan to protect.
     */
    public static function recordChargeFailure(int $subscriptionId): void
    {
        $sub = Db::first('SELECT user_id, status FROM subscriptions WHERE id = ?', [$subscriptionId]);
        if ($sub === null) {
            return;
        }

        $next = match ($sub['status']) {
            'active' => 'grace',
            'grace'  => 'expired',
            default  => 'failed',
        };

        Db::exec(
            "UPDATE subscriptions SET status = ?, next_charge_at = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = ?",
            [$next, $subscriptionId]
        );
        AuditLog::record((int) $sub['user_id'], 'subscription_charge_failed', ['subscription_id' => $subscriptionId, 'new_status' => $next]);
    }

    public static function recordChargeSuccess(int $subscriptionId): void
    {
        $userId = Db::value('SELECT user_id FROM subscriptions WHERE id = ?', [$subscriptionId]);

        Db::exec(
            "UPDATE subscriptions SET status = 'active', last_charge_at = NOW(),
             next_charge_at = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = ?",
            [$subscriptionId]
        );

        if ($userId !== null) {
            AuditLog::record((int) $userId, 'subscription_charge_succeeded', ['subscription_id' => $subscriptionId]);
        }
    }

    /**
     * Deliberately does NOT revoke the session — the account and login stay
     * usable (browsing free content, resubscribing later); only gated
     * routes lose access, on their very next request via RequireSubscription.
     * Reachable from pending/active/grace on purpose — someone who hasn't
     * been charged yet still needs a way out.
     */
    public static function unsubscribe(int $userId): void
    {
        Db::exec(
            "UPDATE subscriptions SET status = 'unsubscribed', unsubscribed_at = NOW()
             WHERE user_id = ? AND status IN ('pending','active','grace')",
            [$userId]
        );
        AuditLog::record($userId, 'subscription_unsubscribed');
    }
}
