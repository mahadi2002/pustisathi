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
use App\Repositories\UserRepo;
use App\Services\Notifier;

/**
 * Forgot/reset password. The raw token only ever exists in the emailed URL —
 * password_reset_tokens.token_hash is a one-way HMAC (App\Core\Crypto::blindIndex()),
 * looked up by exact match, same PII-lookup pattern as everything else in
 * this app. Every response is identical whether or not the email has an
 * account, so this can never be used to enumerate registered emails.
 */
final class PasswordResetController extends Controller
{
    private const TOKEN_TTL_MIN = 30;

    public function forgotForm(Request $request): Response
    {
        return $this->view('public/forgot-password');
    }

    public function forgotSubmit(Request $request): Response
    {
        $v = Validator::make($request->body, [
            'email' => 'required|email|max:191',
        ], ['email' => 'Email']);

        if ($v->fails()) {
            return $this->failValidation($v, '/forgot-password', 'সঠিক Email দিন।');
        }

        $email = strtolower($v->get('email'));
        $user  = (new UserRepo())->findByEmail($email);

        if ($user !== null) {
            $token = Crypto::randomToken(32);
            Db::insert(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                 VALUES (?, ?, NOW() + INTERVAL ? MINUTE)',
                [$user['id'], Crypto::blindIndex('pwreset:' . $token), self::TOKEN_TTL_MIN]
            );
            Notifier::passwordReset(
                (string) $user['email'],
                rtrim((string) config('app.url'), '/') . '/reset-password/' . $token
            );
        }

        Session::notify('success', 'এই Email দিয়ে যদি কোনো Account থাকে, একটি Reset Link পাঠানো হয়েছে।');
        return $this->redirect('/login');
    }

    public function resetForm(Request $request, string $token): Response
    {
        if ($this->findValidToken($token) === null) {
            Session::notify('error', 'এই Reset Link-এর মেয়াদ শেষ হয়ে গেছে অথবা এটি সঠিক নয়। আবার চেষ্টা করুন।');
            return $this->redirect('/forgot-password');
        }

        return $this->view('public/reset-password', ['token' => $token]);
    }

    public function resetSubmit(Request $request, string $token): Response
    {
        $row = $this->findValidToken($token);
        if ($row === null) {
            Session::notify('error', 'এই Reset Link-এর মেয়াদ শেষ হয়ে গেছে অথবা এটি সঠিক নয়। আবার চেষ্টা করুন।');
            return $this->redirect('/forgot-password');
        }

        $v = Validator::make($request->body, [
            'password'              => 'required|min:8|max:255',
            'password_confirmation' => 'required|min:8|max:255',
        ], ['password' => 'Password', 'password_confirmation' => 'Password নিশ্চিতকরণ']);

        if ($v->fails()) {
            return $this->failValidation($v, '/reset-password/' . $token, 'সঠিকভাবে ফর্মটি পূরণ করুন।');
        }

        if ($v->get('password') !== $v->get('password_confirmation')) {
            Session::notify('error', 'দুটো Password মিলছে না।');
            return $this->redirect('/reset-password/' . $token);
        }

        Db::exec('UPDATE users SET password_hash = ? WHERE id = ?', [
            password_hash($v->get('password'), PASSWORD_DEFAULT),
            $row['user_id'],
        ]);
        Db::exec('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?', [$row['id']]);

        // A password reset should kill every other open session on the account.
        Session::revokeAllForUser((int) $row['user_id']);

        Session::notify('success', 'আপনার Password পরিবর্তন হয়েছে। এখন Login করুন।');
        return $this->redirect('/login');
    }

    private function findValidToken(string $token): ?array
    {
        return Db::first(
            'SELECT id, user_id FROM password_reset_tokens
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1',
            [Crypto::blindIndex('pwreset:' . $token)]
        );
    }
}
