<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/** Mobile+OTP is the only way in for every role, so logging out is the only thing left here. */
final class AuthController extends Controller
{
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
