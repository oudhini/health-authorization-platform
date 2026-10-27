<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManagedUserRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-users');

        return view('admin.users.index', [
            'users' => User::query()->with('organization')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-users');

        return view('admin.users.form', [
            'managedUser' => null,
            'roles' => UserRole::cases(),
            'organizations' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function store(ManagedUserRequest $request, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('manage-users');
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $audit, $request): User {
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->role = UserRole::from($data['role']);
            $user->organization_id = $data['organization_id'] ?? null;
            $user->is_active = (bool) $data['is_active'];
            $user->save();

            $audit->record(AuditAction::UserCreated, $request->user(), $user, [
                'role' => $user->role->value,
                'organization_id' => $user->organization_id,
                'is_active' => $user->is_active,
            ]);

            return $user;
        });

        return redirect()->route('admin.users.index')
            ->with('status', 'Le compte a été créé.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('manage-users');

        return view('admin.users.form', [
            'managedUser' => $user,
            'roles' => UserRole::cases(),
            'organizations' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function update(ManagedUserRequest $request, User $user, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('manage-users');
        $data = $request->validated();
        $role = UserRole::from($data['role']);
        $organizationId = isset($data['organization_id']) ? (int) $data['organization_id'] : null;
        $isActive = (bool) $data['is_active'];

        if ($user->is($request->user())
            && ($role !== $user->role || $organizationId !== $user->organization_id || ! $isActive)) {
            return back()->withErrors([
                'role' => 'Vous ne pouvez pas désactiver votre propre compte ni changer votre rôle ou organisation.',
            ])->withInput();
        }

        if ($user->role === UserRole::PlatformAdmin && $user->is_active
            && ($role !== UserRole::PlatformAdmin || ! $isActive)) {
            $otherActiveAdmins = User::query()
                ->where('role', UserRole::PlatformAdmin->value)
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->count();

            if ($otherActiveAdmins === 0) {
                return back()->withErrors([
                    'is_active' => 'Au moins un compte administrateur actif doit être conservé.',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($data, $user, $role, $organizationId, $isActive, $request, $audit): void {
            $oldRole = $user->role;
            $oldOrganizationId = $user->organization_id;
            $oldIsActive = $user->is_active;

            $user->name = $data['name'];
            $user->email = $data['email'];
            if (($data['password'] ?? '') !== '') {
                $user->password = $data['password'];
            }
            $user->role = $role;
            $user->organization_id = $organizationId;
            $user->is_active = $isActive;
            $user->save();

            $audit->record(AuditAction::UserUpdated, $request->user(), $user, [
                'old_role' => $oldRole->value,
                'new_role' => $role->value,
                'old_organization_id' => $oldOrganizationId,
                'new_organization_id' => $organizationId,
                'old_is_active' => $oldIsActive,
                'new_is_active' => $isActive,
                'password_changed' => ($data['password'] ?? '') !== '',
            ]);
        });

        return redirect()->route('admin.users.index')->with('status', 'Le compte a été mis à jour.');
    }
}
