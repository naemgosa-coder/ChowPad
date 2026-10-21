<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;

final class HomeController extends Controller
{
    public function index(): void
    {
        redirect(Auth::check() ? '/dashboard' : '/login');
    }
}
