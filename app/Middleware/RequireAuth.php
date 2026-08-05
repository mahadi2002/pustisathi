<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/** Any authenticated role (patient, nutritionist, or admin). */
final class RequireAuth implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $userId = Session::userId();

        if ($userId === null) {
            Session::notify('info', 'এই অংশ দেখতে আগে Login করুন।');
            return Response::redirect('/auth/login?next=' . rawurlencode($request->path));
        }

        // The account may have been soft-deleted since the session was created.
        $user = Db::first('SELECT id FROM users WHERE id = ? AND deleted_at IS NULL', [$userId]);
        if ($user === null) {
            Session::revokeAllForUser($userId);
            Session::destroy_all();
            return Response::redirect('/auth/login');
        }

        return $next();
    }
}
