<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class OrganizationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-users');

        return view('admin.organizations.index', [
            'organizations' => Organization::query()
                ->withCount('users')
                ->orderBy('type')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function store(StoreOrganizationRequest $request, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('manage-users');
        $data = $request->validated();

        $organization = DB::transaction(function () use ($data, $request, $audit): Organization {
            $organization = Organization::query()->create($data);
            $audit->record(AuditAction::OrganizationCreated, $request->user(), $organization, [
                'type' => $organization->type->value,
            ]);

            return $organization;
        });

        return redirect()->route('admin.organizations.index')
            ->with('status', "L’organisation « {$organization->name} » a été créée.");
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
        AuditLogger $audit,
    ): RedirectResponse {
        Gate::authorize('manage-users');
        DB::transaction(function () use ($request, $organization, $audit): void {
            $oldName = $organization->name;
            $organization->update($request->validated());
            $audit->record(AuditAction::OrganizationRenamed, $request->user(), $organization, [
                'type' => $organization->type->value,
                'name_changed' => $oldName !== $organization->name,
            ]);
        });

        return redirect()->route('admin.organizations.index')
            ->with('status', 'L’organisation a été mise à jour.');
    }
}
