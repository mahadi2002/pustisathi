<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Rules\DietPlanEngine;

final class DashboardController extends Controller
{
    /**
     * Short, factual notes on why each condition changes the plan — mirrors
     * what ConditionRuleEngine actually does (restricted/required tags),
     * not a generic disclaimer, so it explains this specific plan.
     */
    private const CONDITION_TIPS = [
        'diabetic'  => 'রক্তে শর্করা নিয়ন্ত্রণে রাখতে হাই-জিআই খাবার (যেমন সাদা ভাত, চিনি) বাদ দেওয়া হয়েছে এবং ফাইবারযুক্ত খাবার অগ্রাধিকার পেয়েছে।',
        'renal'     => 'কিডনির উপর চাপ কমাতে বেশি পটাশিয়াম, সোডিয়াম ও ফসফরাসযুক্ত খাবার এই প্ল্যান থেকে বাদ দেওয়া হয়েছে।',
        'cardiac'   => 'হৃদযন্ত্র সুস্থ রাখতে বেশি সোডিয়াম ও স্যাচুরেটেড ফ্যাটযুক্ত খাবার এড়ানো হয়েছে, ফাইবার ও ওমেগা-৩ সমৃদ্ধ খাবার অগ্রাধিকার পেয়েছে।',
        'pregnancy' => 'গর্ভাবস্থায় ঝুঁকিপূর্ণ খাবার (কাঁচা/অপাস্তুরিত) বাদ দেওয়া হয়েছে, ফোলেট ও আয়রন সমৃদ্ধ খাবার অগ্রাধিকার পেয়েছে।',
    ];

    private const ACTIVITY_LABELS = [
        'sedentary'   => 'Sedentary (বসে কাজ)',
        'light'       => 'Light (হালকা পরিশ্রম)',
        'moderate'    => 'Moderate (মাঝারি পরিশ্রম)',
        'active'      => 'Active (কায়িক পরিশ্রম)',
        'very_active' => 'Very Active (ভারী পরিশ্রম)',
    ];

    public function index(Request $request): Response
    {
        $userId  = $this->currentUserId();
        $profile = Db::first('SELECT * FROM body_profiles WHERE user_id = ?', [$userId]);

        if ($profile === null) {
            return $this->redirect('/app/onboarding');
        }

        $plan = DietPlanEngine::currentPlan($userId);

        $heightM = ((float) $profile['height_cm']) / 100;
        $bmi     = $heightM > 0 ? ((float) $profile['weight_kg']) / ($heightM * $heightM) : 0.0;
        $bmr     = DietPlanEngine::bmrFor($profile);
        $multiplier = DietPlanEngine::activityMultiplierFor((string) $profile['activity_level']);

        $conditionCodes = $plan !== null ? (json_decode((string) $plan['condition_codes'], true) ?: []) : [];
        $conditionTips  = [];
        foreach ($conditionCodes as $code) {
            $conditionTips[] = [
                'label' => condition_label($code),
                'tip'   => self::CONDITION_TIPS[$code] ?? null,
            ];
        }

        $planAgeDays  = $plan !== null ? (int) floor((time() - strtotime((string) $plan['created_at'])) / 86400) : null;

        return $this->view('patient/dashboard', [
            'profile'          => $profile,
            'plan'             => $plan,
            'bmi'              => $bmi,
            'bmiCategory'      => bmi_category($bmi),
            'bmr'              => $bmr,
            'activityLabel'    => self::ACTIVITY_LABELS[$profile['activity_level']] ?? $profile['activity_level'],
            'activityMultiplier' => $multiplier,
            'conditionTips'    => $conditionTips,
            'planAgeDays'      => $planAgeDays,
        ]);
    }

    /**
     * Generates (or replaces) the short code a nutritionist enters to link
     * this patient — see NutriController::link(). Uppercase hex from
     * random_bytes so it's short enough to read aloud but not guessable.
     */
    public function shareCode(Request $request): Response
    {
        $userId = $this->currentUserId();

        $code = null;
        for ($i = 0; $i < 20; $i++) {
            $candidate = strtoupper(bin2hex(random_bytes(4)));
            if (Db::value('SELECT id FROM body_profiles WHERE share_code = ?', [$candidate]) === null) {
                $code = $candidate;
                break;
            }
        }

        if ($code === null) {
            Session::notify('error', 'কোড তৈরি করা যায়নি, আবার চেষ্টা করুন।');
            return $this->redirect('/app/dashboard');
        }

        Db::exec('UPDATE body_profiles SET share_code = ? WHERE user_id = ?', [$code, $userId]);

        Session::notify('success', 'নতুন কোড তৈরি হয়েছে — এটি আপনার Nutritionist কে দিন।');
        return $this->redirect('/app/dashboard');
    }
}
