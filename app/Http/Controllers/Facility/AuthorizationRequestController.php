<?php

declare(strict_types=1);

namespace App\Http\Controllers\Facility;

use App\Actions\Requests\CancelAuthorizationRequest;
use App\Actions\Requests\CreateAuthorizationRequest;
use App\Actions\Requests\SubmitAuthorizationRequest;
use App\Actions\Requests\UpdateAuthorizationRequest;
use App\Domain\Requests\Exceptions\CannotCreateRequest;
use App\Domain\Requests\Exceptions\CannotSubmitRequest;
use App\Domain\Requests\Exceptions\InvalidStatusTransition;
use App\Domain\Requests\RequestStatus;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\ResubmitAuthorizationRequestRequest;
use App\Http\Requests\Facility\StoreAuthorizationRequestRequest;
use App\Http\Requests\Facility\UpdateAuthorizationRequestRequest;
use App\Models\AuthorizationRequest;
use App\Models\Beneficiary;
use App\Models\MedicalAct;
use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class AuthorizationRequestController extends Controller
{
    public function index(Request $request): View
    {
        // Always scoped to the agent's own facility: never trust an id from the URL here.
        $requests = AuthorizationRequest::query()
            ->where('facility_id', $request->user()->organization_id)
            ->with(['beneficiary', 'insurer'])
            ->latest('id')
            ->paginate(20);

        return view('facility.requests.index', ['requests' => $requests]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', AuthorizationRequest::class);
        $request->validate([
            'insurer_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'min:2', 'max:100', 'regex:/^[\pL\pN\s.\x27’\-]+$/u'],
        ], [
            'search.regex' => 'Recherchez avec un nom ou un numéro composé de lettres, chiffres ou tirets.',
        ]);

        $facility = Organization::query()->findOrFail($request->user()->organization_id);
        $insurers = $facility->partnerInsurers()
            ->where('type', OrganizationType::Insurer->value)
            ->orderBy('name')
            ->get();

        $requestedInsurerId = $request->query('insurer_id');
        $selectedInsurer = null;
        $beneficiaries = collect();
        $searchTerm = is_string($request->query('search')) ? trim($request->query('search')) : '';
        $hasMoreResults = false;

        if ($requestedInsurerId !== null && filter_var($requestedInsurerId, FILTER_VALIDATE_INT) !== false) {
            $selectedInsurer = $insurers->firstWhere('id', (int) $requestedInsurerId);

            abort_if($selectedInsurer === null, 404);

            if (mb_strlen($searchTerm) >= 2) {
                $today = CarbonImmutable::today();
                $matches = Beneficiary::query()
                    ->where('insurer_id', $selectedInsurer->id)
                    ->where(function (Builder $query) use ($searchTerm): void {
                        $query->where('name', 'like', '%'.$searchTerm.'%')
                            ->orWhere('member_number', 'like', '%'.$searchTerm.'%');
                    })
                    ->whereHas('policyholder.subscriber.policies', function (Builder $query) use ($selectedInsurer, $today): void {
                        $query->where('insurer_id', $selectedInsurer->id)->activeOn($today);
                    })
                    ->with([
                        'policyholder.subscriber.policies' => function ($query) use ($selectedInsurer, $today): void {
                            $query->where('insurer_id', $selectedInsurer->id)->activeOn($today);
                        },
                    ])
                    ->orderBy('name')
                    ->limit(21)
                    ->get();

                $hasMoreResults = $matches->count() > 20;
                $beneficiaries = $matches->take(20);
            }
        }

        return view('facility.requests.create', [
            'acts' => MedicalAct::query()->where('is_active', true)->orderBy('label')->get(),
            'insurers' => $insurers,
            'selectedInsurer' => $selectedInsurer,
            'beneficiaries' => $beneficiaries,
            'searchTerm' => $searchTerm,
            'hasMoreResults' => $hasMoreResults,
            'approvedRequests' => $selectedInsurer === null
                ? collect()
                : AuthorizationRequest::query()
                    ->where('facility_id', $request->user()->organization_id)
                    ->where('insurer_id', $selectedInsurer->id)
                    ->where('status', RequestStatus::Approved->value)
                    ->with('beneficiary')
                    ->latest('id')
                    ->get(),
            'rows' => 5,
        ]);
    }

    public function store(
        StoreAuthorizationRequestRequest $request,
        CreateAuthorizationRequest $create,
    ): RedirectResponse {
        Gate::authorize('create', AuthorizationRequest::class);

        try {
            $selection = $request->coverageSelection();
            $authorizationRequest = $create->handle(
                $request->user(),
                $selection['insurer_id'],
                $selection['beneficiary_id'],
                $selection['policy_id'],
                $request->acts(),
                $request->relatedRequestId(),
            );
        } catch (CannotCreateRequest $exception) {
            return back()->withInput()->withErrors(['request' => $exception->getMessage()]);
        }

        return redirect()
            ->route('facility.requests.show', $authorizationRequest)
            ->with('status', 'Demande créée en brouillon. Vérifiez-la puis envoyez-la à l\'assureur.');
    }

    public function show(AuthorizationRequest $authorizationRequest): View
    {
        Gate::authorize('view', $authorizationRequest);

        $authorizationRequest->load([
            'beneficiary', 'insurer', 'relatedRequest', 'items.medicalAct', 'documents', 'authorization',
            'messages.sender',
        ]);

        return view('facility.requests.show', ['authorizationRequest' => $authorizationRequest]);
    }

    public function edit(AuthorizationRequest $authorizationRequest): View
    {
        Gate::authorize('editDraft', $authorizationRequest);
        $authorizationRequest->load('items.medicalAct');
        $existingActIds = $authorizationRequest->items->pluck('medical_act_id');

        return view('facility.requests.edit', [
            'authorizationRequest' => $authorizationRequest,
            'acts' => MedicalAct::query()
                ->where(fn (Builder $query) => $query->where('is_active', true)->orWhereIn('id', $existingActIds))
                ->orderBy('label')
                ->get(),
        ]);
    }

    public function update(
        UpdateAuthorizationRequestRequest $request,
        AuthorizationRequest $authorizationRequest,
        UpdateAuthorizationRequest $update,
    ): RedirectResponse {
        Gate::authorize('editDraft', $authorizationRequest);

        try {
            $update->handle($request->user(), $authorizationRequest, $request->acts());
        } catch (CannotCreateRequest $exception) {
            return back()->withInput()->withErrors(['request' => $exception->getMessage()]);
        } catch (InvalidStatusTransition) {
            return back()->withErrors(['request' => 'Seul un brouillon peut être modifié.']);
        }

        return redirect()
            ->route('facility.requests.show', $authorizationRequest)
            ->with('status', 'Le brouillon a été mis à jour.');
    }

    public function submit(
        ResubmitAuthorizationRequestRequest $request,
        AuthorizationRequest $authorizationRequest,
        SubmitAuthorizationRequest $submit,
    ): RedirectResponse {
        Gate::authorize('submit', $authorizationRequest);

        try {
            $submit->handle($request->user(), $authorizationRequest, $request->responseMessage());
        } catch (CannotSubmitRequest $exception) {
            return back()->withErrors(['request' => $exception->reasons()]);
        } catch (InvalidStatusTransition) {
            return back()->withErrors(['request' => 'Cette demande ne peut pas être envoyée dans son état actuel.']);
        }

        return redirect()
            ->route('facility.requests.show', $authorizationRequest)
            ->with('status', 'Demande envoyée à l\'assureur.');
    }

    public function cancel(
        Request $request,
        AuthorizationRequest $authorizationRequest,
        CancelAuthorizationRequest $cancel,
    ): RedirectResponse {
        Gate::authorize('cancel', $authorizationRequest);

        try {
            $cancel->handle($request->user(), $authorizationRequest);
        } catch (InvalidStatusTransition) {
            return back()->withErrors(['request' => 'Cette demande ne peut plus être annulée.']);
        }

        return redirect()
            ->route('facility.requests.show', $authorizationRequest)
            ->with('status', 'Demande annulée.');
    }
}
