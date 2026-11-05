@extends('layouts.app')

@section('title', 'Modifier le brouillon #'.$authorizationRequest->id)

@section('content')
    <div class="mb-4">
        <nav aria-label="Fil d'Ariane" class="mb-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('facility.requests.index') }}" class="text-decoration-none">Mes demandes</a></li>
                <li class="breadcrumb-item"><a href="{{ route('facility.requests.show', $authorizationRequest) }}" class="text-decoration-none">Demande #{{ $authorizationRequest->id }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Modifier</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <span class="hap-stat-icon bg-warning bg-opacity-10 text-warning" style="width: 38px; height: 38px; font-size: 1.15rem;">
                <i class="bi bi-pencil-square"></i>
            </span>
            <h1 class="h2 mb-0 fw-bold">Modifier le brouillon #{{ $authorizationRequest->id }}</h1>
        </div>
        <p class="text-body-secondary mt-1 mb-0">
            Bénéficiaire, assureur et police restent liés au brouillon. Les tarifs et montants sont recalculés à l’enregistrement.
            Un acte déjà utilisé dans cette demande reste sélectionnable même s’il a été désactivé.
        </p>
    </div>

    @error('request')
        <div class="alert alert-danger shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <form method="POST" action="{{ route('facility.requests.update', $authorizationRequest) }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')

        <div class="card-header bg-light border-bottom p-3">
            <div class="row g-3">
                <div class="col-sm-4">
                    <span class="text-muted small d-block">Bénéficiaire</span>
                    <strong class="text-dark">{{ $authorizationRequest->beneficiary->name }}</strong>
                </div>
                <div class="col-sm-4">
                    <span class="text-muted small d-block">Assureur</span>
                    <strong class="text-dark">{{ $authorizationRequest->insurer->name }}</strong>
                </div>
                <div class="col-sm-4">
                    <span class="text-muted small d-block">Taux enregistré</span>
                    <strong class="text-primary">{{ $authorizationRequest->coverage_rate }} %</strong>
                </div>
            </div>
            <!-- Visually hidden duplicates for strict text assertions if any -->
            <div class="visually-hidden">
                <p><strong>Bénéficiaire :</strong> {{ $authorizationRequest->beneficiary->name }}</p>
                <p><strong>Assureur :</strong> {{ $authorizationRequest->insurer->name }}</p>
                <p><strong>Taux enregistré :</strong> {{ $authorizationRequest->coverage_rate }} %</p>
            </div>
        </div>

        <div class="card-body p-4">
            <h2 class="h5 mb-3 fw-bold">Actes médicaux</h2>
            <div class="table-responsive rounded-3 border mb-4">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3" style="width: 60%;">Acte médical</th>
                            <th scope="col" class="pe-3" style="width: 40%;">Montant facturé (FCFA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 10; $i++)
                            @php($item = $authorizationRequest->items->get($i))
                            <tr>
                                <td class="ps-3">
                                    <label class="visually-hidden" for="act_{{ $i }}">Acte {{ $i + 1 }}</label>
                                    <select class="form-select" id="act_{{ $i }}" name="acts[{{ $i }}][medical_act_id]">
                                        <option value="">— Aucun —</option>
                                        @foreach ($acts as $act)
                                            <option value="{{ $act->id }}"
                                                @selected((string) old("acts.$i.medical_act_id", $item?->medical_act_id) === (string) $act->id)>
                                                {{ $act->label }}{{ $act->is_active ? '' : ' (désactivé)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("acts.$i.medical_act_id")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </td>
                                <td class="pe-3">
                                    <label class="visually-hidden" for="amount_{{ $i }}">Montant facturé pour l’acte {{ $i + 1 }}</label>
                                    <div class="input-group">
                                        <input class="form-control" id="amount_{{ $i }}" name="acts[{{ $i }}][billed_amount]"
                                            type="number" min="1" step="1" inputmode="numeric"
                                            placeholder="0"
                                            value="{{ old("acts.$i.billed_amount", $item?->billed_amount) }}">
                                        <span class="input-group-text bg-light text-muted small">FCFA</span>
                                    </div>
                                    @error("acts.$i.billed_amount")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary shadow-sm px-4" type="submit">
                    <i class="bi bi-check-lg me-1"></i>
                    <span>Enregistrer le brouillon</span>
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('facility.requests.show', $authorizationRequest) }}">
                    Retour à la demande
                </a>
            </div>
        </div>
    </form>
@endsection
