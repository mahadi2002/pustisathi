<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\HttpException;

final class RequireAdmin implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $allowlist = (array) config('app.admin_ip_allowlist', []);
        if ($allowlist !== [] && !in_array($request->ip(), $allowlist, true)) {
            throw new HttpException(403);
        }

        $userId = Session::userId();
        if ($userId === null) {
            return Response::redirect('/login?next=' . rawurlencode($request->path));
        }

        $admin = Db::first(
            'SELECT id, role FROM users WHERE id = ? AND deleted_at IS NULL',
            [$userId]
        );

        if ($admin === null || $admin['role'] !== 'admin') {
            throw new HttpException(403);
        }

        View::share('admin', $admin);

        return $next();
    }
}
