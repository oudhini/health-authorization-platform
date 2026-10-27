@extends('layouts.app')

@section('title', 'Demande #'.$authorizationRequest->id)

@section('content')
    @use('App\Domain\Requests\RequestStatus')

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <a class="link-secondary text-decoration-none" href="{{ route('insurer.requests.index') }}">
                &larr; Demandes reçues
            </a>
            <h1 class="h2 mt-2 mb-0">Demande #{{ $authorizationRequest->id }}</h1>
        </div>
        @include('partials.status-badge', ['status' => $authorizationRequest->status])
    </div>

    @if ($authorizationRequest->decision_note)
        <div class="alert {{ $authorizationRequest->status === RequestStatus::Rejected ? 'alert-danger' : 'alert-warning' }}"
            role="note">
            <strong>Message enregistré :</strong> {{ $authorizationRequest->decision_note }}
        </div>
    @endif

    @if ($authorizationRequest->messages->isNotEmpty())
        <section class="card border-0 shadow-sm mb-4" aria-labelledby="messages-heading">
            <div class="card-body">
                <h2 class="h5" id="messages-heading">Échanges avec l'établissement</h2>
                <ol class="list-group list-group-numbered">
                    @foreach ($authorizationRequest->messages as $message)
                        <li class="list-group-item">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <strong>
                                    {{ $message->type === \App\Enums\RequestMessageType::InformationRequested
                                        ? 'Informations demandées'
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

    @if ($authorizationRequest->authorization)
        <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>Bon généré : <strong>{{ $authorizationRequest->authorization->number }}</strong></span>
            <a class="btn btn-sm btn-success"
                href="{{ route('authorizations.show', $authorizationRequest->authorization) }}">Voir et imprimer le bon</a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Bénéficiaire et établissement</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Établissement</dt>
                        <dd class="col-sm-8">{{ $authorizationRequest->facility->name }}</dd>
                        <dt class="col-sm-4">Bénéficiaire</dt>
                        <dd class="col-sm-8">
                            {{ $authorizationRequest->beneficiary->name }}
                            <span class="text-body-secondary">({{ $authorizationRequest->beneficiary->member_number }})</span>
                        </dd>
                    </dl>
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Transmission</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Envoyée le</dt>
                        <dd class="col-sm-8">{{ $authorizationRequest->submitted_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                        @if (
                            $authorizationRequest->relatedRequest
                            && (int) $authorizationRequest->relatedRequest->insurer_id === (int) $authorizationRequest->insurer_id
                        )
                            <dt class="col-sm-4">Demande liée</dt>
                            <dd class="col-sm-8">
                                @if ($authorizationRequest->relatedRequest->status !== RequestStatus::Draft)
                                    <a href="{{ route('insurer.requests.show', $authorizationRequest->relatedRequest) }}">
                                        #{{ $authorizationRequest->relatedRequest->id }}
                                    </a>
                                @else
                                    #{{ $authorizationRequest->relatedRequest->id }}
                                @endif
                            </dd>
                        @endif
                    </dl>
                </div>
            </section>
        </div>
    </div>

    <section class="mb-4">
        <h2 class="h4">Actes</h2>
        @include('partials.request-items')
    </section>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Documents</h2>
            @if ($authorizationRequest->documents->isEmpty())
                <p class="text-body-secondary mb-0">Aucun document joint.</p>
            @else
                <ul class="list-group list-group-flush">
                    @foreach ($authorizationRequest->documents as $document)
                        <li class="list-group-item px-0">
                            {{ $document->type->label() }} ·
                            <a href="{{ route('documents.download', $document) }}">{{ $document->original_name }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    @if ($authorizationRequest->status === RequestStatus::Submitted)
        <section class="no-print">
            <h2 class="h4">Décision</h2>
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card border-success h-100">
                        <div class="card-body">
                            <h3 class="h5">Approuver</h3>
                            <p class="text-body-secondary">Approuver la demande et générer son bon de prise en charge.</p>
                            <form method="POST" action="{{ route('insurer.requests.approve', $authorizationRequest) }}">
                                @csrf
                                <button type="submit" class="btn btn-success">Approuver et générer le bon</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-warning h-100">
                        <div class="card-body">
                            <h3 class="h5">Demander des informations</h3>
                            <form method="POST"
                                action="{{ route('insurer.requests.request-information', $authorizationRequest) }}">
                                @csrf
                                <label class="form-label" for="info_note">Message à l'établissement</label>
                                <textarea class="form-control mb-3" id="info_note" name="note" rows="3"
                                    maxlength="1000" required>{{ old('note') }}</textarea>
                                <button type="submit" class="btn btn-warning">Demander des informations</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-danger h-100">
                        <div class="card-body">
                            <h3 class="h5">Refuser</h3>
                            <form method="POST" action="{{ route('insurer.requests.reject', $authorizationRequest) }}">
                                @csrf
                                <label class="form-label" for="reject_note">Motif du refus</label>
                                <textarea class="form-control mb-3" id="reject_note" name="note" rows="3"
                                    maxlength="1000" required>{{ old('note') }}</textarea>
                                <button type="submit" class="btn btn-danger">Refuser la demande</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
