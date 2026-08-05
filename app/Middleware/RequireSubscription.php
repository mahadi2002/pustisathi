<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\SubscriptionService;

/**
 * The one rule this whole gate exists to enforce: every gated request
 * re-reads the database. No session flag, no cookie, no token claim ever
 * decides access on its own — a lapsed subscription must lose access on
 * the very next request, not at next login.
 */
final class RequireSubscription implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $userId = Session::userId();

        if ($userId === null || !SubscriptionService::hasAccess($userId)) {
            return Response::redirect('/subscribe');
        }

        return $next();
    }
}
