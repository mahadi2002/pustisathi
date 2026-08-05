<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Gateways\GatewayFactory;

/**
 * One URL (`GET /subscribe`) covers both steps of the OTP funnel — which
 * step renders is decided by whether a pending mobile number is sitting in
 * the session, not by the URL.
 */
final class SubscribeController extends Controller
{
    public function form(Request $request): Response
    {
        $pending = Session::get('otp_pending_mobile');

        return $this->view('public/subscribe', [
            'step'   => $pending !== null ? 'verify' : 'phone',
            'mobile' => $pending,
            'next'   => $request->str('next', '/app/dashboard'),
        ]);
    }

    public function requestOtp(Request $request): Response
    {
        $v = Validator::make($request->body, [
            'mobile' => 'required|mobile',
        ], ['mobile' => 'মোবাইল নম্বর']);

        if ($v->fails()) {
            $v->flash();
            Session::notify('error', $v->firstError() ?? 'সঠিক নম্বর দিন।');
            return $this->redirect('/subscribe');
        }

        $mobile = $v->get('mobile');
        $result = GatewayFactory::make()->requestOtp($mobile);

        if (!$result->sent) {
            Session::notify('error', $result->message ?? 'একটু পরে আবার চেষ্টা করুন।');
            return $this->redirect('/subscribe');
        }

        Session::put('otp_pending_mobile', $mobile);
        Session::put('otp_requested_at', time());
        Session::notify('success', 'একটি কোড পাঠানো হয়েছে।');

        return $this->redirect('/subscribe?next=' . rawurlencode($request->str('next', '/app/dashboard')));
    }

    public function verifyOtp(Request $request): Response
    {
        $mobile = (string) Session::get('otp_pending_mobile', '');
        if ($mobile === '') {
            return $this->redirect('/subscribe');
        }

        $v = Validator::make($request->body, [
            'otp' => 'required|digits:' . (int) config('app.otp.length', 6),
        ], ['otp' => 'কোড']);

        if ($v->fails()) {
            Session::notify('error', $v->firstError() ?? 'সঠিক কোড দিন।');
            return $this->redirect('/subscribe');
        }

        $result = GatewayFactory::make()->verifyOtp($mobile, $v->get('otp'));

        if (!$result->success || $result->userId === null) {
            Session::notify('error', $result->message ?? 'কোড মেলেনি।');
            return $this->redirect('/subscribe');
        }

        Session::forget('otp_pending_mobile');
        Session::forget('otp_requested_at');
        Session::login($result->userId);
        Session::notify('success', 'স্বাগতম! আপনার Subscription সক্রিয় হয়েছে।');

        $hasProfile = (bool) Db::value('SELECT 1 FROM body_profiles WHERE user_id = ?', [$result->userId]);

        $next = $request->str('next', '');
        if (!$hasProfile) {
            return $this->redirect('/app/onboarding');
        }
        if ($next !== '' && str_starts_with($next, '/')) {
            return $this->redirect($next);
        }
        return $this->redirect('/app/dashboard');
    }

    public function status(Request $request): Response
    {
        $mobile = Session::get('otp_pending_mobile');
        if ($mobile === null) {
            return $this->json(['pending' => false]);
        }

        $requestedAt = (int) Session::get('otp_requested_at', 0);
        $ttl         = (int) config('app.otp.ttl', 300);
        $expiresIn   = max(0, $requestedAt + $ttl - time());

        return $this->json([
            'pending'      => true,
            'mobile_last4' => substr((string) $mobile, -4),
            'expires_in'   => $expiresIn,
        ]);
    }
}
