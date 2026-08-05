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
use App\Exceptions\OtpException;
use App\Gateways\MockGateway;
use App\Services\OtpService;
use App\Services\SubscriptionService;

/**
 * Links a mobile number and an active subscription to an *already logged
 * in* nutritionist account — the same OTP mechanics and the same ৳/day
 * price as a patient signing up, just attached to an existing user row
 * instead of creating a new one, since a nutritionist authenticates by
 * email/password, not by phone number.
 */
final class NutriSubscribeController extends Controller
{
    public function form(Request $request): Response
    {
        $user = $this->currentUser();
        if ($user === null || $user['role'] !== 'nutritionist') {
            return $this->redirect('/');
        }
        if (SubscriptionService::hasAccess((int) $user['id'])) {
            return $this->redirect('/nutri');
        }

        $pending = Session::get('nutri_otp_pending_mobile');

        return $this->view('nutri/subscribe', [
            'step'   => $pending !== null ? 'verify' : 'phone',
            'mobile' => $pending,
        ]);
    }

    public function requestOtp(Request $request): Response
    {
        $userId = $this->currentUserId();
        if ($userId === null) {
            return $this->redirect('/auth/login');
        }

        $v = Validator::make($request->body, ['mobile' => 'required|mobile'], ['mobile' => 'মোবাইল নম্বর']);
        if ($v->fails()) {
            Session::notify('error', $v->firstError() ?? 'সঠিক নম্বর দিন।');
            return $this->redirect('/nutri/subscribe');
        }

        try {
            OtpService::request($v->get('mobile'));
        } catch (OtpException $e) {
            Session::notify('error', $e->getMessage());
            return $this->redirect('/nutri/subscribe');
        }

        Session::put('nutri_otp_pending_mobile', $v->get('mobile'));
        Session::notify('success', 'একটি কোড পাঠানো হয়েছে।');

        return $this->redirect('/nutri/subscribe');
    }

    public function verifyOtp(Request $request): Response
    {
        $userId = $this->currentUserId();
        $mobile = (string) Session::get('nutri_otp_pending_mobile', '');
        if ($userId === null || $mobile === '') {
            return $this->redirect('/nutri/subscribe');
        }

        $v = Validator::make($request->body, [
            'otp' => 'required|digits:' . (int) config('app.otp.length', 6),
        ], ['otp' => 'কোড']);

        if ($v->fails()) {
            Session::notify('error', $v->firstError() ?? 'সঠিক কোড দিন।');
            return $this->redirect('/nutri/subscribe');
        }

        try {
            OtpService::verify($mobile, $v->get('otp'));
        } catch (OtpException $e) {
            Session::notify('error', $e->getMessage());
            return $this->redirect('/nutri/subscribe');
        }

        $operator = MockGateway::detectOperator($mobile);

        Db::exec(
            'UPDATE users SET mobile = ?, mobile_hash = ?, operator = ? WHERE id = ?',
            [Crypto::encrypt($mobile), Crypto::blindIndex('mobile:' . $mobile), $operator, $userId]
        );

        SubscriptionService::activate($userId, $operator, 'mock');

        Session::forget('nutri_otp_pending_mobile');
        Session::notify('success', 'স্বাগতম! আপনার Subscription সক্রিয় হয়েছে।');

        return $this->redirect('/nutri');
    }
}
