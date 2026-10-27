<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /** Rate limiting is applied by the route middleware throttle:login. */
    public function store(LoginRequest $request, AuditLogger $audit): RedirectResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials + ['is_active' => true])) {
            $audit->record(AuditAction::LoginFailed, null, null, ['email' => Str::lower($credentials['email'])]);

            // One generic message: never reveal whether the e-mail exists.
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Identifiants incorrects.']);
        }

        // Prevents session fixation: a new session id after authentication.
        $request->session()->regenerate();
        $audit->record(AuditAction::LoginSucceeded, Auth::user());

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $audit->record(AuditAction::Logout, $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
