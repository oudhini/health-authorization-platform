<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\AuditAction;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsActive
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            $this->audit->record(AuditAction::InactiveAccountSessionEnded, $user);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Ce compte est désactivé. Contactez l’administrateur.']);
        }

        return $next($request);
    }
}
