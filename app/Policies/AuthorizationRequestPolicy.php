<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Requests\RequestStatus;
use App\Enums\UserRole;
use App\Models\AuthorizationRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may do what on a request (docs/actors-and-roles.md, section 3).
 *
 * Another organization's request answers 404, not 403: a 403 would confirm
 * that the identifier exists (protection against identifier guessing, IDOR).
 * Status rules (can this status change?) live in RequestStatus, not here.
 */
final class AuthorizationRequestPolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::FacilityAgent && $user->organization_id !== null;
    }

    public function view(User $user, AuthorizationRequest $request): Response
    {
        $allowed = match ($user->role) {
            UserRole::FacilityAgent => $this->belongsToFacility($user, $request),
            // An insurer never sees a draft: it has not been sent yet.
            UserRole::InsurerManager => $this->isAddressedTo($user, $request)
                && $request->status !== RequestStatus::Draft,
            UserRole::PlatformAdmin => false,
        };

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }

    public function submit(User $user, AuthorizationRequest $request): Response
    {
        return $this->facilityOwnerOnly($user, $request);
    }

    public function editDraft(User $user, AuthorizationRequest $request): Response
    {
        $allowed = $user->role === UserRole::FacilityAgent
            && $this->belongsToFacility($user, $request)
            && (int) $request->created_by === (int) $user->id
            && $request->status === RequestStatus::Draft;

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }

    public function cancel(User $user, AuthorizationRequest $request): Response
    {
        return $this->facilityOwnerOnly($user, $request);
    }

    public function attachDocument(User $user, AuthorizationRequest $request): Response
    {
        $owner = $this->facilityOwnerOnly($user, $request);

        if ($owner->denied()) {
            return $owner;
        }

        return $request->isEditable()
            ? Response::allow()
            : Response::deny('Cette demande n\'accepte plus de documents.');
    }

    public function decide(User $user, AuthorizationRequest $request): Response
    {
        $allowed = $user->role === UserRole::InsurerManager
            && $this->isAddressedTo($user, $request)
            && $request->status !== RequestStatus::Draft;

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }

    private function facilityOwnerOnly(User $user, AuthorizationRequest $request): Response
    {
        $allowed = $user->role === UserRole::FacilityAgent && $this->belongsToFacility($user, $request);

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }

    private function belongsToFacility(User $user, AuthorizationRequest $request): bool
    {
        return (int) $user->organization_id === (int) $request->facility_id;
    }

    private function isAddressedTo(User $user, AuthorizationRequest $request): bool
    {
        return (int) $user->organization_id === (int) $request->insurer_id;
    }
}
