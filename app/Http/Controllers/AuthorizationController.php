<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Models\Authorization;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class AuthorizationController extends Controller
{
    public function show(Request $request, Authorization $authorization, AuditLogger $audit): View
    {
        Gate::authorize('view', $authorization);

        $authorization->load([
            'authorizationRequest.beneficiary.policyholder',
            'authorizationRequest.facility',
            'authorizationRequest.insurer',
            'authorizationRequest.policy',
            'authorizationRequest.items.medicalAct',
        ]);

        $audit->record(AuditAction::AuthorizationViewed, $request->user(), $authorization);

        return view('authorizations.show', [
            'authorization' => $authorization,
            'authorizationRequest' => $authorization->authorizationRequest,
            'validity' => $authorization->validityStatus(CarbonImmutable::today()),
        ]);
    }
}
