<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/**
 * Reached only once RequireNutritionist has already confirmed approval and
 * an active subscription. The patient roster and plan editor aren't built
 * yet, so this is a real landing page in the meantime, not a dead end.
 */
final class NutriController extends Controller
{
    public function home(Request $request): Response
    {
        return $this->view('nutri/home');
    }
}
