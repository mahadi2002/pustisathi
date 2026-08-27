<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/** Keeps an already-signed-in user out of the login/register funnel — sent to their own role's home instead. */
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

        return Response::redirect(match ($role) {
            'admin'        => '/admin',
            'nutritionist' => '/nutri',
            'patient'      => '/app/dashboard',
            default        => '/',
        });
    }
}
