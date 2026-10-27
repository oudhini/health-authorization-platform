<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        return $request->user() === null
            ? view('landing')
            : redirect()->route('dashboard');
    }
}
