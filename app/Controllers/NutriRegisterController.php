<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\OtpException;
use App\Gateways\MockGateway;
use App\Repositories\UserRepo;
use App\Services\OtpService;
use App\Services\SubscriptionService;

/**
 * Registering as a nutritionist is the same OTP mechanism as the patient
 * subscribe funnel, just with a credentials field collected up front and a
 * different account role at the end — there's no email/password path
 * anywhere in the app, this is the only way in for a brand-new
 * nutritionist.
 */
final class NutriRegisterController extends Controller
{
    public function form(Request $request): Response
    {
        $pending = Session::get('nutri_reg_pending_mobile');

        return $this->view('nutri/register', [
            'step'        => $pending !== null ? 'verify' : 'phone',
            'mobile'      => $pending,
            'credentials' => Session::get('nutri_reg_pending_credentials'),
        ]);
    }

    public function requestOtp(Request $request): Response
    {
        $v = Validator::make($request->body, [
            'mobile'      => 'required|mobile',
            'credentials' => 'required|min:10|max:255',
        ], ['mobile' => 'মোবাইল নম্বর', 'credentials' => 'Credential/License তথ্য']);

        if ($v->fails()) {
            return $this->failValidation($v, '/nutri/register', 'সঠিকভাবে ফর্মটি পূরণ করুন।', flash: true);
        }

        try {
            OtpService::request($v->get('mobile'));
        } catch (OtpException $e) {
            Session::notify('error', $e->getMessage());
            return $this->redirect('/nutri/register');
        }

        Session::put('nutri_reg_pending_mobile', $v->get('mobile'));
        Session::put('nutri_reg_pending_credentials', $v->get('credentials'));
        Session::notify('success', 'একটি কোড পাঠানো হয়েছে।');

        return $this->redirect('/nutri/register');
    }

    public function verifyOtp(Request $request): Response
    {
        $mobile      = (string) Session::get('nutri_reg_pending_mobile', '');
        $credentials = (string) Session::get('nutri_reg_pending_credentials', '');
        if ($mobile === '' || $credentials === '') {
            return $this->redirect('/nutri/register');
        }

        $v = Validator::make($request->body, [
            'otp' => 'required|digits:' . (int) config('app.otp.length', 6),
        ], ['otp' => 'কোড']);

        if ($v->fails()) {
            return $this->failValidation($v, '/nutri/register', 'সঠিক কোড দিন।');
        }

        try {
            OtpService::verify($mobile, $v->get('otp'));
        } catch (OtpException $e) {
            Session::notify('error', $e->getMessage());
            return $this->redirect('/nutri/register');
        }

        $operator = MockGateway::detectOperator($mobile);
        [$userId] = (new UserRepo())->findOrCreateNutritionist($mobile, $operator, $credentials);

        SubscriptionService::activate($userId, $operator, (string) config('gateway.driver', 'mock'));

        Session::forget('nutri_reg_pending_mobile');
        Session::forget('nutri_reg_pending_credentials');
        Session::login($userId);
        Session::notify('success', 'আপনার Application জমা হয়েছে এবং Subscription সক্রিয় হয়েছে।');

        return $this->redirect('/nutri');
    }
}
