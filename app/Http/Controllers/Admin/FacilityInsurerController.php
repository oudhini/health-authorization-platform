<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFacilityInsurersRequest;
use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class FacilityInsurerController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-facility-insurers');

        return view('admin.facility-insurers.index', [
            'facilities' => Organization::query()
                ->where('type', OrganizationType::Facility)
                ->with('partnerInsurers')
                ->orderBy('name')
                ->get(),
            'insurers' => Organization::query()
                ->where('type', OrganizationType::Insurer)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(
        UpdateFacilityInsurersRequest $request,
        Organization $facility,
        AuditLogger $audit,
    ): RedirectResponse {
        Gate::authorize('manage-facility-insurers');
        abort_unless($facility->type === OrganizationType::Facility, 404);

        $insurerIds = array_map('intval', $request->validated('insurer_ids', []));
        $validInsurerCount = Organization::query()
            ->where('type', OrganizationType::Insurer)
            ->whereKey($insurerIds)
            ->count();

        if ($validInsurerCount !== count($insurerIds)) {
            throw ValidationException::withMessages([
                'insurer_ids' => 'Sélectionnez uniquement des organisations de type assureur.',
            ]);
        }

        DB::transaction(function () use ($facility, $insurerIds, $request, $audit): void {
            $facility->partnerInsurers()->sync($insurerIds);
            $audit->record(AuditAction::FacilityInsurersUpdated, $request->user(), $facility, [
                'insurer_ids' => implode(',', $insurerIds),
            ]);
        });

        return redirect()
            ->route('admin.facility-insurers.index')
            ->with('status', 'Les assureurs partenaires de l’établissement ont été mis à jour.');
    }
}
