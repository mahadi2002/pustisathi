<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\UserRepo;
use PDOException;

/** Patient registration: email + password. Explicit signup, not an auto-created-on-first-login account. */
final class RegisterController extends Controller
{
    public function form(Request $request): Response
    {
        return $this->view('public/register');
    }

    public function store(Request $request): Response
    {
        $v = Validator::make($request->body, [
            'email'                 => 'required|email|max:191',
            'password'              => 'required|min:8|max:255',
            'password_confirmation' => 'required|min:8|max:255',
        ], [
            'email'                 => 'Email',
            'password'              => 'Password',
            'password_confirmation' => 'Password নিশ্চিতকরণ',
        ]);

        if ($v->fails()) {
            return $this->failValidation($v, '/register', 'সঠিকভাবে ফর্মটি পূরণ করুন।', flash: true);
        }

        if ($v->get('password') !== $v->get('password_confirmation')) {
            Session::notify('error', 'দুটো Password মিলছে না।');
            return $this->redirect('/register');
        }

        $email = strtolower($v->get('email'));
        $users = new UserRepo();

        if ($users->findByEmail($email) !== null) {
            // Generic — never confirm whether an account already exists for this email.
            Session::notify('error', 'এই Email দিয়ে Register করা যায়নি। ইতিমধ্যে Account থাকলে Login করুন।');
            return $this->redirect('/register');
        }

        try {
            $userId = $users->createPatient($email, password_hash($v->get('password'), PASSWORD_DEFAULT));
        } catch (PDOException $e) {
            // Race: another request registered the same email between the
            // findByEmail check above and this insert. The email column has
            // a UNIQUE constraint (001_users_auth.sql) — MySQL error 1062.
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                Session::notify('error', 'এই Email দিয়ে Register করা যায়নি। ইতিমধ্যে Account থাকলে Login করুন।');
                return $this->redirect('/register');
            }
            throw $e;
        }

        Session::login($userId);
        Session::notify('success', 'স্বাগতম! আপনার Account তৈরি হয়েছে।');

        return $this->redirect('/app/onboarding');
    }
}
