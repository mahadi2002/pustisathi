<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;

/** Meant for an external uptime monitor — actually touches the DB, not just a 200. */
final class HealthController extends Controller
{
    public function index(Request $request): Response
    {
        try {
            $ok = Db::value('SELECT 1') === 1;
        } catch (\Throwable) {
            $ok = false;
        }

        return $this->json([
            'ok'        => $ok,
            'db'        => $ok ? 'up' : 'down',
            'timestamp' => date('c'),
        ], $ok ? 200 : 503);
    }
}
