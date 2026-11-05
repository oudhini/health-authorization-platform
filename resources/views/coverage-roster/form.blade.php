@extends('layouts.app')

@section('title', $beneficiary ? 'Corriger un dossier assuré' : 'Ajouter un bénéficiaire')

@section('content')
    <div class="mb-4">
        <nav aria-label="Fil d'Ariane" class="mb-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('coverage-roster.index', ['insurer_id' => $insurer?->id]) }}" class="text-decoration-none">Portefeuille assuré</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $beneficiary ? 'Corriger' : 'Nouveau dossier' }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                <i class="bi {{ $beneficiary ? 'bi-pencil-square' : 'bi-person-plus-fill' }}"></i>
            </span>
            <h1 class="h2 mb-0 fw-bold">{{ $beneficiary ? 'Corriger un dossier assuré' : 'Ajouter un bénéficiaire' }}</h1>
        </div>
        <p class="text-body-secondary mt-1 mb-0">
            {{ $insurer?->name ?? 'Sélectionnez un assureur' }} · Les informations saisies ici alimentent le répertoire de recherche.
        </p>
    </div>

    <form method="POST"
        action="{{ $beneficiary ? route('coverage-roster.update', $beneficiary) : route('coverage-roster.store') }}"
        class="card border-0 shadow-sm overflow-hidden">
        @csrf
        @if ($beneficiary)
            @method('PUT')
            <input type="hidden" name="policy_id" value="{{ old('policy_id', $record['policy_id'] ?? '') }}">
        @endif

        <div class="card-body p-4">
            @if (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
                <div class="mb-4 p-3 bg-light rounded-3 border">
                    <label class="form-label fw-bold" for="insurer_id">Assureur</label>
                    <select class="form-select @error('insurer_id') is-invalid @enderror" id="insurer_id" name="insurer_id" required>
                        <option value="">Choisir</option>
                        @foreach ($insurers as $option)
                            <option value="{{ $option->id }}" @selected(old('insurer_id', $insurer?->id) == $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                    @error('insurer_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            @else
                <input type="hidden" name="insurer_id" value="{{ $insurer->id }}">
            @endif

            <!-- Contract & Subscriber -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                <h2 class="h5 mb-0 fw-bold">Contrat et souscripteur</h2>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="subscriber_name">Souscripteur (entreprise)</label>
                    <input class="form-control" id="subscriber_name" name="subscriber_name" value="{{ old('subscriber_name', $record['subscriber_name'] ?? '') }}" required maxlength="255">
                    @error('subscriber_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="policyholder_name">Assuré principal</label>
                    <input class="form-control" id="policyholder_name" name="policyholder_name" value="{{ old('policyholder_name', $record['policyholder_name'] ?? '') }}" required maxlength="255">
                    @error('policyholder_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="policy_number">Numéro de contrat</label>
                    <input class="form-control" id="policy_number" name="policy_number" value="{{ old('policy_number', $record['policy_number'] ?? '') }}" required maxlength="50">
                    @error('policy_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="coverage_rate">Taux de prise en charge (%)</label>
                    <div class="input-group">
                        <input class="form-control" type="number" min="0" max="100" id="coverage_rate" name="coverage_rate" value="{{ old('coverage_rate', $record['coverage_rate'] ?? 100) }}" required>
                        <span class="input-group-text bg-light text-muted small">%</span>
                    </div>
                    @error('coverage_rate')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="starts_on">Début du contrat</label>
                    <input class="form-control" type="date" id="starts_on" name="starts_on" value="{{ old('starts_on', $record['starts_on'] ?? '') }}" required>
                    @error('starts_on')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="ends_on">Fin du contrat</label>
                    <input class="form-control" type="date" id="ends_on" name="ends_on" value="{{ old('ends_on', $record['ends_on'] ?? '') }}" required>
                    @error('ends_on')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Beneficiary Information -->
            <div class="pt-3 border-top mt-2">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-person-badge text-primary fs-5"></i>
                    <h2 class="h5 mb-0 fw-bold">Bénéficiaire</h2>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="beneficiary_name">Nom complet</label>
                        <input class="form-control" id="beneficiary_name" name="beneficiary_name" value="{{ old('beneficiary_name', $record['beneficiary_name'] ?? '') }}" required maxlength="255">
                        @error('beneficiary_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="member_number">Numéro de bénéficiaire</label>
                        <input class="form-control" id="member_number" name="member_number" value="{{ old('member_number', $record['member_number'] ?? '') }}" required maxlength="50">
                        @error('member_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="birth_date">Date de naissance</label>
                        <input class="form-control" type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', $record['birth_date'] ?? '') }}" required>
                        @error('birth_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="relationship">Lien avec l’assuré principal</label>
                        <select class="form-select" id="relationship" name="relationship" required>
                            @foreach (['self' => 'Assuré principal', 'spouse' => 'Conjoint(e)', 'child' => 'Enfant', 'other' => 'Autre'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('relationship', $record['relationship'] ?? 'self') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('relationship')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-light p-4 border-top d-flex flex-wrap gap-2">
            <button class="btn btn-primary shadow-sm px-4" type="submit">
                <i class="bi bi-check-lg me-1"></i>
                {{ $beneficiary ? 'Enregistrer la correction' : 'Créer le dossier' }}
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('coverage-roster.index', ['insurer_id' => $insurer?->id]) }}">
                Annuler
            </a>
        </div>
    </form>
@endsection
