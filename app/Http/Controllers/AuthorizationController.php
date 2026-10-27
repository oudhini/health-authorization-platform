<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Models\Authorization;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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

    public function downloadPdf(Request $request, Authorization $authorization, AuditLogger $audit): Response
    {
        Gate::authorize('view', $authorization);

        $authorization->load([
            'authorizationRequest.beneficiary.policyholder',
            'authorizationRequest.facility',
            'authorizationRequest.insurer',
            'authorizationRequest.policy',
            'authorizationRequest.items.medicalAct',
        ]);

        $content = Pdf::loadView('authorizations.pdf', [
            'authorization' => $authorization,
            'authorizationRequest' => $authorization->authorizationRequest,
        ])
            ->setPaper('a4')
            ->setOption([
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
            ])
            ->output();

        $audit->record(AuditAction::AuthorizationDownloaded, $request->user(), $authorization);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$authorization->number.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
