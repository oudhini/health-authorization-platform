@extends('layouts.app')

@section('title', 'Demande #'.$authorizationRequest->id)

@section('content')
    @use('App\Enums\DocumentType')
    @use('App\Domain\Requests\RequestStatus')

    <nav aria-label="Fil d'Ariane" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('facility.requests.index') }}" class="text-decoration-none">Mes demandes</a></li>
            <li class="breadcrumb-item active" aria-current="page">Demande #{{ $authorizationRequest->id }}</li>
        </ol>
    </nav>

    <!-- Top Header Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
        <div class="d-flex align-items-center gap-3">
            <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-file-earmark-medical"></i>
            </span>
            <div>
                <h1 class="h2 mb-0 fw-bold">Demande #{{ $authorizationRequest->id }}</h1>
                <span class="text-body-secondary small">Créée le {{ $authorizationRequest->created_at->format('d/m/Y à H:i') }}</span>
            </div>
            @include('partials.status-badge', ['status' => $authorizationRequest->status])
        </div>

        @if ($authorizationRequest->status === RequestStatus::Draft
            && (int) $authorizationRequest->created_by === (int) auth()->id()
            && auth()->user()->role === \App\Enums\UserRole::FacilityAgent)
            <a class="btn btn-outline-primary no-print shadow-sm" href="{{ route('facility.requests.edit', $authorizationRequest) }}">
                <i class="bi bi-pencil-square me-1"></i>
                Modifier le brouillon
            </a>
        @endif
    </div>

    @if ($authorizationRequest->decision_note)
        <div class="alert {{ $authorizationRequest->status === RequestStatus::Rejected ? 'alert-danger' : 'alert-warning' }} shadow-sm d-flex align-items-start gap-2 mb-4" role="note">
            <i class="bi {{ $authorizationRequest->status === RequestStatus::Rejected ? 'bi-x-circle-fill' : 'bi-exclamation-circle-fill' }} fs-5 flex-shrink-0 mt-1"></i>
            <div>
                <strong>Message de l'assureur :</strong> {{ $authorizationRequest->decision_note }}
            </div>
        </div>
    @endif

    @if ($authorizationRequest->messages->isNotEmpty())
        <section class="card border-0 shadow-sm mb-4" aria-labelledby="messages-heading">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-chat-left-text text-primary"></i>
                <h2 class="h5 mb-0 fw-bold" id="messages-heading">Échanges avec l'assureur</h2>
            </div>
            <div class="card-body p-4">
                <ol class="list-group list-group-numbered rounded-3">
                    @foreach ($authorizationRequest->messages as $message)
                        <li class="list-group-item p-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <strong class="text-dark">
                                    {{ $message->type === \App\Enums\RequestMessageType::InformationRequested
                                        ? 'Demande d’informations de l’assureur'
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

    <!-- Beneficiary & Insurance Details -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                <dl class="col-md-6 mb-0">
                    <dt class="text-body-secondary small text-uppercase fw-semibold mb-1">Bénéficiaire</dt>
                    <dd class="fs-6 fw-bold text-dark mb-3">
                        <i class="bi bi-person me-1 text-primary"></i>
                        {{ $authorizationRequest->beneficiary->name }} ({{ $authorizationRequest->beneficiary->member_number }})
                    </dd>
                    <dt class="text-body-secondary small text-uppercase fw-semibold mb-1">Assureur</dt>
                    <dd class="fs-6 fw-bold text-dark mb-0">
                        <i class="bi bi-shield-check me-1 text-primary"></i>
                        {{ $authorizationRequest->insurer->name }}
                    </dd>
                </dl>
                <dl class="col-md-6 mb-0">
                    <dt class="text-body-secondary small text-uppercase fw-semibold mb-1">Créée le</dt>
                    <dd class="mb-3 text-secondary">
                        <i class="bi bi-calendar3 me-1"></i>
                        {{ $authorizationRequest->created_at->format('d/m/Y H:i') }}
                    </dd>
                    @if ($authorizationRequest->submitted_at)
                        <dt class="text-body-secondary small text-uppercase fw-semibold mb-1">Envoyée le</dt>
                        <dd class="mb-3 text-secondary">
                            <i class="bi bi-send me-1"></i>
                            {{ $authorizationRequest->submitted_at->format('d/m/Y H:i') }}
                        </dd>
                    @endif
                    @if ($authorizationRequest->relatedRequest)
                        <dt class="text-body-secondary small text-uppercase fw-semibold mb-1">Demande liée</dt>
                        <dd class="mb-0">
                            <a class="badge text-bg-primary text-decoration-none py-2 px-3" href="{{ route('facility.requests.show', $authorizationRequest->relatedRequest) }}">
                                <i class="bi bi-link-45deg me-1"></i>
                                #{{ $authorizationRequest->relatedRequest->id }}
                            </a>
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    @if ($authorizationRequest->authorization && Route::has('authorizations.show'))
        <div class="card border-success border-2 shadow-sm mb-4 bg-success bg-opacity-10">
            <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="hap-stat-icon bg-success text-white">
                        <i class="bi bi-check2-circle"></i>
                    </span>
                    <div>
                        <h2 class="h5 mb-1 text-success fw-bold">Prise en charge validée</h2>
                        <p class="mb-0 text-dark">Cette demande est approuvée.</p>
                    </div>
                </div>
                <a class="btn btn-success shadow-sm px-4" href="{{ route('authorizations.show', $authorizationRequest->authorization) }}">
                    <i class="bi bi-printer me-1"></i>
                    Voir et imprimer le bon {{ $authorizationRequest->authorization->number }}
                </a>
            </div>
        </div>
    @endif

    <!-- Medical Acts Section -->
    <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
        <h2 class="h4 mb-0 fw-bold">Actes</h2>
        <span class="badge rounded-pill text-bg-secondary">{{ $authorizationRequest->items->count() }} acte(s)</span>
    </div>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            @include('partials.request-items')
        </div>
    </div>

    <!-- Documents Section -->
    <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
        <h2 class="h4 mb-0 fw-bold">Documents</h2>
        <span class="badge rounded-pill text-bg-secondary">{{ $authorizationRequest->documents->count() }} fichier(s)</span>
    </div>
    @if ($authorizationRequest->documents->isEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4 text-center text-body-secondary">
                <i class="bi bi-file-earmark-arrow-up fs-2 d-block mb-1 text-muted"></i>
                <p class="mb-0">Aucun document joint.</p>
            </div>
        </div>
    @else
        <ul class="list-group shadow-sm border-0 mb-3 rounded-3">
            @foreach ($authorizationRequest->documents as $document)
                <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
                    <span class="badge rounded-pill text-bg-light border text-dark fw-semibold">
                        <i class="bi bi-paperclip me-1"></i>
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

    @if ($authorizationRequest->requiredDocumentTypes() !== [])
        <div class="alert alert-warning shadow-sm d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
            <div>
                Document obligatoire pour envoyer cette demande :
                <strong>{{ collect($authorizationRequest->requiredDocumentTypes())->map(fn ($type) => $type->label())->implode(', ') }}</strong>.
            </div>
        </div>
    @endif

    @if ($authorizationRequest->isEditable())
        @if ($authorizationRequest->status === RequestStatus::InfoRequested)
            <div class="alert alert-info shadow-sm d-flex align-items-center gap-2 mb-4" role="status">
                <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
                <div>
                    Répondez au message de l'assureur, ajoutez un document complémentaire, ou faites les deux avant de renvoyer la demande.
                </div>
            </div>
        @endif

        <!-- Document Upload Form -->
        <form method="POST" action="{{ route('facility.documents.store', $authorizationRequest) }}"
            enctype="multipart/form-data" class="card border-0 shadow-sm no-print mb-4">
            @csrf
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="h5 mb-0 fw-bold">Ajouter un document</h2>
            </div>
            <div class="card-body p-4">
                @if ($authorizationRequest->status === RequestStatus::InfoRequested)
                    <p class="text-body-secondary small mb-3">
                        Le document ajouté sera transmis avec votre réponse lorsque vous renverrez la demande.
                    </p>
                @endif
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="type">Type de document</label>
                        <select class="form-select" id="type" name="type" required>
                            @foreach (DocumentType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="document">Fichier (PDF, JPEG ou PNG, 5 Mo maximum)</label>
                        <input class="form-control" id="document" name="document" type="file"
                            accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-upload me-1"></i>
                            Ajouter
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Final Action Bar -->
        <div class="card border-0 shadow-sm p-4 bg-light no-print">
            <div class="d-flex flex-column gap-3">
                <form method="POST" action="{{ route('facility.requests.submit', $authorizationRequest) }}">
                    @csrf
                    @if ($authorizationRequest->status === RequestStatus::InfoRequested)
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="response">Votre réponse (facultatif si vous ajoutez un document)</label>
                            <textarea class="form-control" id="response" name="response" rows="3" maxlength="1000"
                                placeholder="Indiquez ici les précisions ou compléments demandés..."
                                aria-describedby="response-error">{{ old('response') }}</textarea>
                            @error('response')
                                <div class="text-danger small mt-1" id="response-error">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary shadow-sm px-4">
                            <i class="bi bi-send-fill me-1"></i>
                            {{ $authorizationRequest->status === RequestStatus::InfoRequested ? 'Répondre et renvoyer à l’assureur' : 'Envoyer à l’assureur' }}
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('facility.requests.cancel', $authorizationRequest) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-x-circle me-1"></i>
                        Annuler la demande
                    </button>
                </form>
            </div>
        </div>
    @endif
@endsection
