<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Crypto;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Rules\DietPlanEngine;

final class OnboardingController extends Controller
{
    private const CONDITION_CODES = ['diabetic', 'renal', 'cardiac', 'pregnancy'];

    public function form(Request $request): Response
    {
        $userId  = $this->currentUserId();
        $profile = Db::first('SELECT * FROM body_profiles WHERE user_id = ?', [$userId]);
        $regions = Db::all('SELECT id, district, upazila FROM regions ORDER BY district, upazila');

        $flags = [];
        if ($profile !== null && $profile['medical_flags_enc'] !== null) {
            $json  = Crypto::decrypt((string) $profile['medical_flags_enc']);
            $flags = $json !== null ? (json_decode($json, true) ?: []) : [];
        }

        return $this->view('patient/onboarding', [
            'profile'    => $profile,
            'regions'    => $regions,
            'flags'      => $flags,
            'conditions' => self::CONDITION_CODES,
        ]);
    }

    public function store(Request $request): Response
    {
        $userId = $this->currentUserId();

        $v = Validator::make($request->body, [
            'age'            => 'required|int|between:1,120',
            'sex'            => 'required|in:male,female,other',
            'height_cm'      => 'required|numeric|between:50,250',
            'weight_kg'      => 'required|numeric|between:10,300',
            'activity_level' => 'required|in:sedentary,light,moderate,active,very_active',
            'budget_tier'    => 'required|in:low,mid,high',
            'region_id'      => 'int',
        ], [
            'age' => 'বয়স', 'sex' => 'লিঙ্গ', 'height_cm' => 'উচ্চতা', 'weight_kg' => 'ওজন',
            'activity_level' => 'Activity Level', 'budget_tier' => 'বাজেট', 'region_id' => 'এলাকা',
        ]);

        if ($v->fails()) {
            return $this->failValidation($v, '/app/onboarding', 'সঠিকভাবে ফর্মটি পূরণ করুন।', flash: true);
        }

        $selectedFlags = array_values(array_intersect($request->arr('medical_flags'), self::CONDITION_CODES));
        $encFlags      = $selectedFlags !== [] ? Crypto::encrypt(json_encode($selectedFlags, JSON_UNESCAPED_UNICODE)) : null;
        $regionId      = $request->int('region_id') ?: null;

        $existing = Db::value('SELECT id FROM body_profiles WHERE user_id = ?', [$userId]);

        if ($existing !== null) {
            Db::exec(
                'UPDATE body_profiles SET age = ?, sex = ?, height_cm = ?, weight_kg = ?, activity_level = ?,
                 region_id = ?, budget_tier = ?, medical_flags_enc = ? WHERE user_id = ?',
                [
                    $v->get('age'), $v->get('sex'), $v->get('height_cm'), $v->get('weight_kg'), $v->get('activity_level'),
                    $regionId, $v->get('budget_tier'), $encFlags, $userId,
                ]
            );
        } else {
            Db::insert(
                'INSERT INTO body_profiles (user_id, age, sex, height_cm, weight_kg, activity_level, region_id, budget_tier, medical_flags_enc)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId, $v->get('age'), $v->get('sex'), $v->get('height_cm'), $v->get('weight_kg'),
                    $v->get('activity_level'), $regionId, $v->get('budget_tier'), $encFlags,
                ]
            );
        }

        DietPlanEngine::generate($userId, $userId);

        Session::notify('success', 'আপনার প্রোফাইল সংরক্ষণ হয়েছে এবং নতুন Diet Plan তৈরি হয়েছে।');
        return $this->redirect('/app/dashboard');
    }
}
