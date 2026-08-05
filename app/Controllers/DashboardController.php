<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Rules\DietPlanEngine;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $userId  = $this->currentUserId();
        $profile = Db::first('SELECT * FROM body_profiles WHERE user_id = ?', [$userId]);

        if ($profile === null) {
            return $this->redirect('/app/onboarding');
        }

        $plan = DietPlanEngine::currentPlan($userId);

        return $this->view('patient/dashboard', [
            'profile' => $profile,
            'plan'    => $plan,
        ]);
    }
}
