<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\UserRepo;

/** One email+password login for every role — role lives on the user row, read at the middleware layer. */
final class LoginController extends Controller
{
    public function form(Request $request): Response
    {
        return $this->view('public/login', ['next' => $request->str('next', '')]);
    }

    public function store(Request $request): Response
    {
        $v = Validator::make($request->body, [
            'email'    => 'required|email|max:191',
            'password' => 'required|max:255',
        ], ['email' => 'Email', 'password' => 'Password']);

        if ($v->fails()) {
            return $this->failValidation($v, '/login', 'সঠিক Email ও Password দিন।');
        }

        $email = strtolower($v->get('email'));
        $user  = (new UserRepo())->findByEmail($email);

        // Constant-ish work whether or not the account exists, so timing does
        // not reveal which emails are registered.
        $hash  = $user['password_hash'] ?? '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy';
        $valid = password_verify($v->get('password'), (string) $hash);

        if (!$valid || $user === null) {
            // Generic — never confirm whether the email itself has an account.
            Session::notify('error', 'Email অথবা Password সঠিক নয়।');
            return $this->redirect('/login');
        }

        Session::login((int) $user['id']);
        Session::notify('success', 'স্বাগতম!');

        $next = $request->str('next', '');
        if (
            $next !== ''
            && str_starts_with($next, '/')
            && !str_starts_with($next, '//')
            && !str_contains($next, '\\')
            && parse_url($next, PHP_URL_HOST) === null
        ) {
            return $this->redirect($next);
        }

        return $this->redirect(match ($user['role']) {
            'admin'        => '/admin',
            'nutritionist' => '/nutri',
            default        => '/app/dashboard',
        });
    }
}
