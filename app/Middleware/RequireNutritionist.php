<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\HttpException;
use App\Services\SubscriptionService;

/**
 * role='nutritionist' AND nutritionist_status='approved' AND an active
 * subscription — approval and billing are separate gates. Approval is
 * never self-service (registering only gets you to 'pending'); billing
 * works exactly like a patient's, same OTP flow, same price, just linked
 * to an account that already exists instead of creating a new one.
 */
final class RequireNutritionist implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $userId = Session::userId();
        if ($userId === null) {
            return Response::redirect('/subscribe?next=' . rawurlencode($request->path));
        }

        $user = Db::first(
            'SELECT id, role, nutritionist_status FROM users WHERE id = ? AND deleted_at IS NULL',
            [$userId]
        );

        if ($user === null || $user['role'] !== 'nutritionist') {
            throw new HttpException(403);
        }

        if ($user['nutritionist_status'] !== 'approved') {
            return Response::html(View::render('nutri/pending', ['status' => $user['nutritionist_status']]));
        }

        if (!SubscriptionService::hasAccess($userId)) {
            Session::notify('info', 'আপনার Subscription সক্রিয় নেই — একই Mobile Number দিয়ে আবার OTP verify করে সক্রিয় করুন।');
            return Response::redirect('/subscribe');
        }

        View::share('currentNutritionistId', (int) $user['id']);

        return $next();
    }
}
