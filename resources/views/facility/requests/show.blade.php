@extends('layouts.app')

@section('title', 'Demande #'.$authorizationRequest->id)

@section('content')
    @use('App\Enums\DocumentType')
    @use('App\Domain\Requests\RequestStatus')

    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <h1 class="h2 mb-0">Demande #{{ $authorizationRequest->id }}</h1>
        @include('partials.status-badge', ['status' => $authorizationRequest->status])
    </div>

    @if ($authorizationRequest->decision_note)
        <div class="alert {{ $authorizationRequest->status === RequestStatus::Rejected ? 'alert-danger' : 'alert-warning' }}" role="note">
            <strong>Message de l'assureur :</strong> {{ $authorizationRequest->decision_note }}
        </div>
    @endif

    @if ($authorizationRequest->messages->isNotEmpty())
        <section class="card shadow-sm mb-4" aria-labelledby="messages-heading">
            <div class="card-body">
                <h2 class="h5" id="messages-heading">Échanges avec l'assureur</h2>
                <ol class="list-group list-group-numbered">
                    @foreach ($authorizationRequest->messages as $message)
                        <li class="list-group-item">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <strong>
                                    {{ $message->type === \App\Enums\RequestMessageType::InformationRequested
                                        ? 'Demande d’informations de l’assureur'
                                        : 'Réponse de l’établissement' }}
                                </strong>
                                <time class="text-body-secondary" datetime="{{ $message->created_at->toIso8601String() }}">
                                    {{ $message->created_at->format('d/m/Y H:i') }}
                                </time>
                            </div>
                            <p class="mb-0 mt-2">{{ $message->body }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4">
                <dl class="col-md-6 mb-0">
                    <dt class="text-body-secondary small">Bénéficiaire</dt>
                    <dd>{{ $authorizationRequest->beneficiary->name }} ({{ $authorizationRequest->beneficiary->member_number }})</dd>
                    <dt class="text-body-secondary small">Assureur</dt>
                    <dd class="mb-0">{{ $authorizationRequest->insurer->name }}</dd>
                </dl>
                <dl class="col-md-6 mb-0">
                    <dt class="text-body-secondary small">Créée le</dt>
                    <dd>{{ $authorizationRequest->created_at->format('d/m/Y H:i') }}</dd>
                    @if ($authorizationRequest->submitted_at)
                        <dt class="text-body-secondary small">Envoyée le</dt>
                        <dd>{{ $authorizationRequest->submitted_at->format('d/m/Y H:i') }}</dd>
                    @endif
                    @if ($authorizationRequest->relatedRequest)
                        <dt class="text-body-secondary small">Demande liée</dt>
                        <dd class="mb-0">
                            <a href="{{ route('facility.requests.show', $authorizationRequest->relatedRequest) }}">
                                #{{ $authorizationRequest->relatedRequest->id }}
                            </a>
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    @if ($authorizationRequest->authorization && Route::has('authorizations.show'))
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <p>Cette demande est approuvée.</p>
                <a class="btn btn-primary" href="{{ route('authorizations.show', $authorizationRequest->authorization) }}">
                    Voir et imprimer le bon {{ $authorizationRequest->authorization->number }}
                </a>
            </div>
        </div>
    @endif

    <h2 class="h4 mt-4">Actes</h2>
    <div class="card shadow-sm">
        <div class="card-body">
            @include('partials.request-items')
        </div>
    </div>

    <h2 class="h4 mt-4">Documents</h2>
    @if ($authorizationRequest->documents->isEmpty())
        <p class="text-body-secondary">Aucun document joint.</p>
    @else
        <ul class="list-group mb-3">
            @foreach ($authorizationRequest->documents as $document)
                <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span>{{ $document->type->label() }}</span>
                    <a href="{{ route('documents.download', $document) }}">{{ $document->original_name }}</a>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($authorizationRequest->requiredDocumentTypes() !== [])
        <p class="text-body-secondary">
            Document obligatoire pour envoyer cette demande :
            {{ collect($authorizationRequest->requiredDocumentTypes())->map(fn ($type) => $type->label())->implode(', ') }}.
        </p>
    @endif

    @if ($authorizationRequest->isEditable())
        @if ($authorizationRequest->status === RequestStatus::InfoRequested)
            <div class="alert alert-info" role="status">
                Répondez au message de l'assureur, ajoutez un document complémentaire, ou faites les deux avant de renvoyer la demande.
            </div>
        @endif

        <form method="POST" action="{{ route('facility.documents.store', $authorizationRequest) }}"
            enctype="multipart/form-data" class="card shadow-sm no-print">
            @csrf
            <div class="card-body">
                <h2 class="h5">Ajouter un document</h2>
                @if ($authorizationRequest->status === RequestStatus::InfoRequested)
                    <p class="text-body-secondary">Le document ajouté sera transmis avec votre réponse lorsque vous renverrez la demande.</p>
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
                        <button type="submit" class="btn btn-outline-primary w-100">Ajouter</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="d-flex flex-wrap gap-2 mt-3 no-print">
            <form method="POST" action="{{ route('facility.requests.submit', $authorizationRequest) }}">
                @csrf
                @if ($authorizationRequest->status === RequestStatus::InfoRequested)
                    <label class="form-label" for="response">Votre réponse (facultatif si vous ajoutez un document)</label>
                    <textarea class="form-control mb-2" id="response" name="response" rows="3" maxlength="1000"
                        aria-describedby="response-error">{{ old('response') }}</textarea>
                    @error('response')
                        <div class="text-danger mb-2" id="response-error">{{ $message }}</div>
                    @enderror
                @endif
                <button type="submit" class="btn btn-primary">
                    {{ $authorizationRequest->status === RequestStatus::InfoRequested ? 'Répondre et renvoyer à l’assureur' : 'Envoyer à l’assureur' }}
                </button>
            </form>
            <form method="POST" action="{{ route('facility.requests.cancel', $authorizationRequest) }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger">Annuler la demande</button>
            </form>
        </div>
    @endif
@endsection
