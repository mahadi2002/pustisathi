<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\SubscriptionService;

/**
 * Keeps a signed-in user with live access out of the login/subscribe
 * funnel — but a logged-in user whose subscription has lapsed is let
 * through rather than bounced, since re-running OTP on their own number is
 * exactly how they reactivate. Without this check, a lapsed nutritionist
 * or patient sent back here to fix billing would just get redirected
 * straight back to the page that told them to come here.
 */
final class GuestOnly implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $userId = Session::userId();
        if ($userId === null) {
            return $next();
        }

        $role = Db::value('SELECT role FROM users WHERE id = ? AND deleted_at IS NULL', [$userId]);
        if ($role === null) {
            return $next();
        }

        $hasLiveAccess = $role === 'admin' || SubscriptionService::hasAccess($userId);
        if (!$hasLiveAccess) {
            return $next();
        }

        return Response::redirect(match ($role) {
            'admin'        => '/admin',
            'nutritionist' => '/nutri',
            'patient'      => '/app/dashboard',
            default        => '/',
        });
    }
}
