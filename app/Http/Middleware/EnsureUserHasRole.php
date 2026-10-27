<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage in routes: ->middleware('role:facility_agent') or 'role:a,b'.
 * UserRole::from() throws on a typo, so a wrong role name fails loudly
 * instead of silently locking everybody out or letting everybody in.
 */
final class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(fn (string $role): UserRole => UserRole::from($role), $roles);
        $user = $request->user();

        if ($user === null || ! in_array($user->role, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
