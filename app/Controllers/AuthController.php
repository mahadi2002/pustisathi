<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\UserRepo;

/**
 * One URL handles both credential paths: a submitted `email` is staff
 * (nutritionist/admin, bcrypt password); a submitted `mobile` hands off to
 * the /subscribe OTP funnel, since for patients "log back in" and "confirm
 * the subscription is still active" are the same OTP check, not two
 * separate mechanisms.
 */
final class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->view('public/login', ['next' => $request->str('next', '/')]);
        }

        if ($request->str('mobile') !== '') {
            return $this->redirect('/subscribe?next=' . rawurlencode($request->str('next', '/app/dashboard')));
        }

        $v = Validator::make($request->body, [
            'email'    => 'required|email',
            'password' => 'required|min:1',
        ], ['email' => 'Email', 'password' => 'Password']);

        if ($v->fails()) {
            $v->flash();
            Session::notify('error', 'সঠিক Email ও Password দিন।');
            return $this->redirect('/auth/login');
        }

        $user = (new UserRepo())->findByEmail($v->get('email'));

        if ($user === null
            || $user['password_hash'] === null
            || !password_verify((string) $request->str('password'), (string) $user['password_hash'])
            || !in_array($user['role'], ['nutritionist', 'admin'], true)
        ) {
            Session::notify('error', 'Email অথবা Password সঠিক নয়।');
            return $this->redirect('/auth/login');
        }

        Session::login((int) $user['id']);

        $next = $request->str('next', '');
        if ($next !== '' && str_starts_with($next, '/')) {
            return $this->redirect($next);
        }

        return $this->redirect(match ($user['role']) {
            'admin'        => '/admin',
            'nutritionist' => '/nutri/patients',
            default        => '/',
        });
    }

    public function registerNutritionist(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->view('public/register-nutritionist');
        }

        $v = Validator::make($request->body, [
            'email'       => 'required|email',
            'password'    => 'required|min:8',
            'credentials' => 'required|min:10|max:255',
        ], ['email' => 'Email', 'password' => 'Password', 'credentials' => 'Credential/License তথ্য']);

        if ($v->fails()) {
            $v->flash();
            return $this->redirect('/auth/register-nutritionist');
        }

        if ((new UserRepo())->findByEmail($v->get('email')) !== null) {
            Session::notify('error', 'এই Email দিয়ে আগে থেকেই একটি Account আছে।');
            return $this->redirect('/auth/register-nutritionist');
        }

        $userId = (new UserRepo())->createStaff(
            $v->get('email'),
            password_hash($v->get('password'), PASSWORD_BCRYPT, ['cost' => 12]),
            'nutritionist',
            $v->get('credentials')
        );

        Session::login($userId);
        Session::notify('info', 'আপনার Application জমা হয়েছে — Admin অনুমোদনের অপেক্ষায় আছে।');

        return $this->redirect('/nutri/patients');
    }

    public function logout(Request $request): Response
    {
        $userId = $this->currentUserId();
        if ($userId !== null) {
            Session::revokeAllForUser($userId);
        }
        Session::destroy_all();
        return $this->redirect('/');
    }
}
