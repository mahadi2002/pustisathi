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

/**
 * Nutritionist registration: email + password + a credentials/license text
 * field, landing in nutritionist_status='pending' — approval is never
 * self-service, an admin has to approve it (see RequireNutritionist).
 */
final class NutriRegisterController extends Controller
{
    public function form(Request $request): Response
    {
        return $this->view('nutri/register');
    }

    public function store(Request $request): Response
    {
        $v = Validator::make($request->body, [
            'email'                 => 'required|email|max:191',
            'password'              => 'required|min:8|max:255',
            'password_confirmation' => 'required|min:8|max:255',
            'credentials'           => 'required|min:10|max:255',
        ], [
            'email'                 => 'Email',
            'password'              => 'Password',
            'password_confirmation' => 'Password নিশ্চিতকরণ',
            'credentials'           => 'Credential/License তথ্য',
        ]);

        if ($v->fails()) {
            return $this->failValidation($v, '/nutri/register', 'সঠিকভাবে ফর্মটি পূরণ করুন।', flash: true);
        }

        if ($v->get('password') !== $v->get('password_confirmation')) {
            Session::notify('error', 'দুটো Password মিলছে না।');
            return $this->redirect('/nutri/register');
        }

        $email = strtolower($v->get('email'));
        $users = new UserRepo();

        if ($users->findByEmail($email) !== null) {
            Session::notify('error', 'এই Email দিয়ে Register করা যায়নি। ইতিমধ্যে Account থাকলে Login করুন।');
            return $this->redirect('/nutri/register');
        }

        try {
            $userId = $users->createNutritionist(
                $email,
                password_hash($v->get('password'), PASSWORD_DEFAULT),
                $v->get('credentials')
            );
        } catch (PDOException $e) {
            // Race: another request registered the same email between the
            // findByEmail check above and this insert. The email column has
            // a UNIQUE constraint (001_users_auth.sql) — MySQL error 1062.
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                Session::notify('error', 'এই Email দিয়ে Register করা যায়নি। ইতিমধ্যে Account থাকলে Login করুন।');
                return $this->redirect('/nutri/register');
            }
            throw $e;
        }

        Session::login($userId);
        Session::notify('success', 'আপনার Application জমা হয়েছে — Admin Approve করলে জানতে পারবেন।');

        return $this->redirect('/nutri');
    }
}
