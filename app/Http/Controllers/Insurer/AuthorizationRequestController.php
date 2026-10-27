<?php

declare(strict_types=1);

namespace App\Http\Controllers\Insurer;

use App\Actions\Requests\DecideAuthorizationRequest;
use App\Domain\Requests\Exceptions\InvalidStatusTransition;
use App\Domain\Requests\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Insurer\DecisionNoteRequest;
use App\Models\AuthorizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class AuthorizationRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requested = $request->query('status');
        $filter = is_string($requested) ? RequestStatus::tryFrom($requested) : null;

        $query = AuthorizationRequest::query()
            ->where('insurer_id', $request->user()->organization_id)
            ->where('status', '!=', RequestStatus::Draft->value);

        if ($filter !== null && $filter !== RequestStatus::Draft) {
            $query->where('status', $filter->value);
        }

        $requests = $query
            ->with(['beneficiary', 'facility'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('insurer.requests.index', [
            'requests' => $requests,
            'filter' => $filter,
            'statuses' => array_filter(
                RequestStatus::cases(),
                fn (RequestStatus $status): bool => $status !== RequestStatus::Draft,
            ),
        ]);
    }

    public function show(AuthorizationRequest $authorizationRequest): View
    {
        Gate::authorize('view', $authorizationRequest);

        $authorizationRequest->load([
            'beneficiary',
            'facility',
            'relatedRequest',
            'items.medicalAct',
            'documents',
            'authorization',
            'messages.sender',
        ]);

        return view('insurer.requests.show', ['authorizationRequest' => $authorizationRequest]);
    }

    public function approve(
        Request $request,
        AuthorizationRequest $authorizationRequest,
        DecideAuthorizationRequest $decide,
    ): RedirectResponse {
        Gate::authorize('decide', $authorizationRequest);

        try {
            $decide->approve($request->user(), $authorizationRequest);
        } catch (InvalidStatusTransition) {
            return back()->withErrors(['request' => 'Cette demande a déjà été traitée ou ne peut pas être approuvée.']);
        }

        return redirect()
            ->route('insurer.requests.show', $authorizationRequest)
            ->with('status', 'Demande approuvée : le bon de prise en charge a été généré.');
    }

    public function reject(
        DecisionNoteRequest $request,
        AuthorizationRequest $authorizationRequest,
        DecideAuthorizationRequest $decide,
    ): RedirectResponse {
        Gate::authorize('decide', $authorizationRequest);

        try {
            $decide->reject($request->user(), $authorizationRequest, $request->validated('note'));
        } catch (InvalidStatusTransition) {
            return back()->withErrors(['request' => 'Cette demande a déjà été traitée ou ne peut pas être refusée.']);
        }

        return redirect()
            ->route('insurer.requests.show', $authorizationRequest)
            ->with('status', 'Demande refusée.');
    }

    public function requestInformation(
        DecisionNoteRequest $request,
        AuthorizationRequest $authorizationRequest,
        DecideAuthorizationRequest $decide,
    ): RedirectResponse {
        Gate::authorize('decide', $authorizationRequest);

        try {
            $decide->requestInformation($request->user(), $authorizationRequest, $request->validated('note'));
        } catch (InvalidStatusTransition) {
            return back()->withErrors([
                'request' => 'Cette demande a déjà été traitée ou ne peut plus recevoir de demande d\'informations.',
            ]);
        }

        return redirect()
            ->route('insurer.requests.show', $authorizationRequest)
            ->with('status', 'Informations demandées à l\'établissement.');
    }
}
