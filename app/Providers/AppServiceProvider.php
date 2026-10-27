<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage-facility-insurers', fn (User $user): bool => $user->role === UserRole::PlatformAdmin);
        Gate::define('manage-coverage-roster', fn (User $user): bool => in_array(
            $user->role,
            [UserRole::InsurerManager, UserRole::PlatformAdmin],
            true,
        ));

        // Brute-force protection: 5 attempts per minute for one e-mail from one address.
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by(
            Str::lower((string) $request->input('email')).'|'.$request->ip()
        ));

        // Limits automated probing of beneficiary records on the facility portal.
        RateLimiter::for('requests', fn (Request $request): Limit => Limit::perMinute(30)->by(
            (string) ($request->user()?->getAuthIdentifier() ?? $request->ip())
        ));
    }
}
