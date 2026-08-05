<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('public/home');
    }

    public function calculator(Request $request): Response
    {
        return $this->view('public/calculator');
    }
}
