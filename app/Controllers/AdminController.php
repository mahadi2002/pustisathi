<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;

/**
 * Reached only once RequireAdmin has already confirmed the role (and IP
 * allowlist, if configured). The CRUD panels aren't built yet, so this is
 * a real landing page with live counts in the meantime, not a dead end.
 */
final class AdminController extends Controller
{
    public function home(Request $request): Response
    {
        $stats = Db::first(
            "SELECT
                (SELECT COUNT(*) FROM users WHERE role = 'patient' AND deleted_at IS NULL) AS patients,
                (SELECT COUNT(*) FROM users WHERE role = 'nutritionist' AND deleted_at IS NULL) AS nutritionists,
                (SELECT COUNT(*) FROM users WHERE role = 'nutritionist' AND nutritionist_status = 'pending' AND deleted_at IS NULL) AS nutritionists_pending,
                (SELECT COUNT(*) FROM food_items) AS food_items,
                (SELECT COUNT(*) FROM diet_plans) AS diet_plans"
        ) ?? [];

        return $this->view('admin/home', ['stats' => $stats]);
    }
}
