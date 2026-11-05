@extends('layouts.app')

@section('title', 'Demande #'.$authorizationRequest->id)

@section('content')
    @use('App\Domain\Requests\RequestStatus')

    <div class="mb-3">
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('insurer.requests.index') }}">
            <i class="bi bi-arrow-left me-1"></i>
            &larr; Demandes reçues
        </a>
    </div>

    <!-- Header Cockpit Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
        <div class="d-flex align-items-center gap-3">
            <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-shield-check"></i>
            </span>
            <div>
                <h1 class="h2 mt-0 mb-0 fw-bold">Demande #{{ $authorizationRequest->id }}</h1>
                <span class="text-body-secondary small">Reçue de l'établissement {{ $authorizationRequest->facility->name }}</span>
            </div>
            @include('partials.status-badge', ['status' => $authorizationRequest->status])
        </div>
    </div>

    @if ($authorizationRequest->decision_note)
        <div class="alert {{ $authorizationRequest->status === RequestStatus::Rejected ? 'alert-danger' : 'alert-warning' }} shadow-sm d-flex align-items-start gap-2 mb-4"
            role="note">
            <i class="bi {{ $authorizationRequest->status === RequestStatus::Rejected ? 'bi-x-circle-fill' : 'bi-exclamation-circle-fill' }} fs-5 flex-shrink-0 mt-1"></i>
            <div>
                <strong>Message enregistré :</strong> {{ $authorizationRequest->decision_note }}
            </div>
        </div>
    @endif

    @if ($authorizationRequest->messages->isNotEmpty())
        <section class="card border-0 shadow-sm mb-4" aria-labelledby="messages-heading">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-chat-dots-fill text-primary"></i>
                <h2 class="h5 mb-0 fw-bold" id="messages-heading">Échanges avec l'établissement</h2>
            </div>
            <div class="card-body p-4">
                <ol class="list-group list-group-numbered rounded-3">
                    @foreach ($authorizationRequest->messages as $message)
                        <li class="list-group-item p-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <strong class="text-dark">
                                    {{ $message->type === \App\Enums\RequestMessageType::InformationRequested
                                        ? 'Informations demandées'
                                        : 'Réponse de l’établissement' }}
                                </strong>
                                <time class="text-body-secondary small" datetime="{{ $message->created_at->toIso8601String() }}">
                                    <i class="bi bi-clock me-1"></i>{{ $message->created_at->format('d/m/Y H:i') }}
                                </time>
                            </div>
                            <p class="mb-0 mt-2 text-secondary">{{ $message->body }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    @if ($authorizationRequest->authorization)
        <div class="alert alert-success shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check2-circle fs-4"></i>
                <span>Bon généré : <strong>{{ $authorizationRequest->authorization->number }}</strong></span>
            </div>
            <a class="btn btn-sm btn-success shadow-sm px-3"
                href="{{ route('authorizations.show', $authorizationRequest->authorization) }}">
                <i class="bi bi-printer me-1"></i>
                Voir et imprimer le bon
            </a>
        </div>
    @endif

    <!-- Beneficiary and Transmission Summary -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                    <i class="bi bi-hospital text-primary"></i>
                    <h2 class="h5 mb-0 fw-bold">Bénéficiaire et établissement</h2>
                </div>
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary small text-uppercase fw-semibold mb-2">Établissement</dt>
                        <dd class="col-sm-8 fw-semibold text-dark mb-2">{{ $authorizationRequest->facility->name }}</dd>

                        <dt class="col-sm-4 text-secondary small text-uppercase fw-semibold mb-0">Bénéficiaire</dt>
                        <dd class="col-sm-8 mb-0">
                            <span class="fw-bold text-dark">{{ $authorizationRequest->beneficiary->name }}</span>
                            <span class="text-body-secondary small d-block">({{ $authorizationRequest->beneficiary->member_number }})</span>
                        </dd>
                    </dl>
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i>
                    <h2 class="h5 mb-0 fw-bold">Transmission</h2>
                </div>
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary small text-uppercase fw-semibold mb-2">Envoyée le</dt>
                        <dd class="col-sm-8 text-secondary mb-2">{{ $authorizationRequest->submitted_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                        @if (
                            $authorizationRequest->relatedRequest
                            && (int) $authorizationRequest->relatedRequest->insurer_id === (int) $authorizationRequest->insurer_id
                        )
                            <dt class="col-sm-4 text-secondary small text-uppercase fw-semibold mb-0">Demande liée</dt>
                            <dd class="col-sm-8 mb-0">
                                @if ($authorizationRequest->relatedRequest->status !== RequestStatus::Draft)
                                    <a class="badge text-bg-primary text-decoration-none py-1 px-2" href="{{ route('insurer.requests.show', $authorizationRequest->relatedRequest) }}">
                                        <i class="bi bi-link-45deg me-1"></i>#{{ $authorizationRequest->relatedRequest->id }}
                                    </a>
                                @else
                                    <span class="badge text-bg-secondary">#{{ $authorizationRequest->relatedRequest->id }}</span>
                                @endif
                            </dd>
                        @endif
                    </dl>
                </div>
            </section>
        </div>
    </div>

    <!-- Medical Acts -->
    <section class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h4 mb-0 fw-bold">Actes</h2>
            <span class="badge rounded-pill text-bg-secondary">{{ $authorizationRequest->items->count() }} acte(s)</span>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @include('partials.request-items')
            </div>
        </div>
    </section>

    <!-- Documents -->
    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-paperclip text-primary"></i>
                <h2 class="h5 mb-0 fw-bold">Documents</h2>
            </div>
            <span class="badge rounded-pill text-bg-secondary">{{ $authorizationRequest->documents->count() }} joint(s)</span>
        </div>
        <div class="card-body p-4">
            @if ($authorizationRequest->documents->isEmpty())
                <p class="text-body-secondary mb-0 text-center py-2">
                    <i class="bi bi-file-earmark-x me-1"></i>
                    Aucun document joint.
                </p>
            @else
                <ul class="list-group list-group-flush">
                    @foreach ($authorizationRequest->documents as $document)
                        <li class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between">
                            <span class="badge rounded-pill text-bg-light border text-dark">
                                {{ $document->type->label() }}
                            </span>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('documents.download', $document) }}">
                                <i class="bi bi-download me-1"></i>
                                {{ $document->original_name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <!-- Insurer Decision Cockpit (3 Cards) -->
    @if ($authorizationRequest->status === RequestStatus::Submitted)
        <section class="no-print pt-2">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-ui-radios-grid text-primary fs-5"></i>
                <h2 class="h4 mb-0 fw-bold">Décision</h2>
            </div>
            <div class="row g-4">
                <!-- Approve -->
                <div class="col-lg-4">
                    <div class="card border-success border-2 shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 text-success mb-2">
                                <i class="bi bi-check-circle-fill fs-5"></i>
                                <h3 class="h5 mb-0 fw-bold">Approuver</h3>
                            </div>
                            <p class="text-body-secondary small flex-grow-1">
                                Approuver la demande et générer son bon de prise en charge.
                            </p>
                            <form method="POST" action="{{ route('insurer.requests.approve', $authorizationRequest) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 shadow-sm py-2">
                                    <i class="bi bi-check2-circle me-1"></i>
                                    Approuver et générer le bon
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Request Information -->
                <div class="col-lg-4">
                    <div class="card border-warning border-2 shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 text-warning mb-2">
                                <i class="bi bi-question-circle-fill fs-5"></i>
                                <h3 class="h5 mb-0 fw-bold text-dark">Demander des informations</h3>
                            </div>
                            <form method="POST" action="{{ route('insurer.requests.request-information', $authorizationRequest) }}" class="d-flex flex-column flex-grow-1">
                                @csrf
                                <label class="form-label small text-secondary" for="info_note">Message à l'établissement</label>
                                <textarea class="form-control mb-3 flex-grow-1" id="info_note" name="note" rows="3"
                                    maxlength="1000" placeholder="Précisez les pièces ou justifications requises..." required>{{ old('note') }}</textarea>
                                <button type="submit" class="btn btn-warning w-100 shadow-sm py-2 text-white">
                                    <i class="bi bi-envelope-paper me-1"></i>
                                    Demander des informations
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Reject -->
                <div class="col-lg-4">
                    <div class="card border-danger border-2 shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 text-danger mb-2">
                                <i class="bi bi-x-circle-fill fs-5"></i>
                                <h3 class="h5 mb-0 fw-bold">Refuser</h3>
                            </div>
                            <form method="POST" action="{{ route('insurer.requests.reject', $authorizationRequest) }}" class="d-flex flex-column flex-grow-1">
                                @csrf
                                <label class="form-label small text-secondary" for="reject_note">Motif du refus</label>
                                <textarea class="form-control mb-3 flex-grow-1" id="reject_note" name="note" rows="3"
                                    maxlength="1000" placeholder="Indiquez le motif contractuel de rejet..." required>{{ old('note') }}</textarea>
                                <button type="submit" class="btn btn-danger w-100 shadow-sm py-2">
                                    <i class="bi bi-slash-circle me-1"></i>
                                    Refuser la demande
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
