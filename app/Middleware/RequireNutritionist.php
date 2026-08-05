<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\HttpException;

/**
 * role='nutritionist' AND nutritionist_status='approved' — approval is a
 * separate admin-driven gate on top of the subscription/auth gate. Never
 * self-service: registering only gets you to 'pending'.
 */
final class RequireNutritionist implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $userId = Session::userId();
        if ($userId === null) {
            return Response::redirect('/auth/login?next=' . rawurlencode($request->path));
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

        View::share('currentNutritionistId', (int) $user['id']);

        return $next();
    }
}
