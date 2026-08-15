<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Rules\DietPlanEngine;

final class PlanController extends Controller
{
    public function show(Request $request): Response
    {
        if (($blocked = $this->requireProfile()) !== null) {
            return $blocked;
        }

        $plan = DietPlanEngine::currentPlan($this->currentUserId());

        return $this->view('patient/plan', ['plan' => $plan]);
    }

    public function regenerate(Request $request): Response
    {
        if (($blocked = $this->requireProfile()) !== null) {
            return $blocked;
        }

        $userId = $this->currentUserId();
        DietPlanEngine::generate($userId, $userId);
        Session::notify('success', 'নতুন Diet Plan তৈরি হয়েছে।');

        return $this->redirect('/app/plan');
    }

    private function requireProfile(): ?Response
    {
        $userId = $this->currentUserId();
        if (Db::value('SELECT 1 FROM body_profiles WHERE user_id = ?', [$userId]) === null) {
            return $this->redirect('/app/onboarding');
        }
        return null;
    }
}
