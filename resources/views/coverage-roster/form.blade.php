@extends('layouts.app')

@section('title', $beneficiary ? 'Corriger un dossier assuré' : 'Ajouter un bénéficiaire')

@section('content')
    <h1 class="h2">{{ $beneficiary ? 'Corriger un dossier assuré' : 'Ajouter un bénéficiaire' }}</h1>
    <p class="text-body-secondary">
        {{ $insurer?->name ?? 'Sélectionnez un assureur' }} · Les informations saisies ici alimentent le répertoire de recherche.
    </p>

    <form method="POST"
        action="{{ $beneficiary ? route('coverage-roster.update', $beneficiary) : route('coverage-roster.store') }}"
        class="card border-0 shadow-sm">
        @csrf
        @if ($beneficiary)
            @method('PUT')
            <input type="hidden" name="policy_id" value="{{ old('policy_id', $record['policy_id'] ?? '') }}">
        @endif
        <div class="card-body">
            @if (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
                <div class="mb-3">
                    <label class="form-label" for="insurer_id">Assureur</label>
                    <select class="form-select" id="insurer_id" name="insurer_id" required>
                        <option value="">Choisir</option>
                        @foreach ($insurers as $option)
                            <option value="{{ $option->id }}" @selected(old('insurer_id', $insurer?->id) == $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                    @error('insurer_id')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            @else
                <input type="hidden" name="insurer_id" value="{{ $insurer->id }}">
            @endif

            <h2 class="h5">Contrat et souscripteur</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="subscriber_name">Souscripteur (entreprise)</label>
                    <input class="form-control" id="subscriber_name" name="subscriber_name" value="{{ old('subscriber_name', $record['subscriber_name'] ?? '') }}" required maxlength="255">
                    @error('subscriber_name')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="policyholder_name">Assuré principal</label>
                    <input class="form-control" id="policyholder_name" name="policyholder_name" value="{{ old('policyholder_name', $record['policyholder_name'] ?? '') }}" required maxlength="255">
                    @error('policyholder_name')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="policy_number">Numéro de contrat</label>
                    <input class="form-control" id="policy_number" name="policy_number" value="{{ old('policy_number', $record['policy_number'] ?? '') }}" required maxlength="50">
                    @error('policy_number')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="coverage_rate">Taux de prise en charge (%)</label>
                    <input class="form-control" type="number" min="0" max="100" id="coverage_rate" name="coverage_rate" value="{{ old('coverage_rate', $record['coverage_rate'] ?? 100) }}" required>
                    @error('coverage_rate')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="starts_on">Début du contrat</label>
                    <input class="form-control" type="date" id="starts_on" name="starts_on" value="{{ old('starts_on', $record['starts_on'] ?? '') }}" required>
                    @error('starts_on')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="ends_on">Fin du contrat</label>
                    <input class="form-control" type="date" id="ends_on" name="ends_on" value="{{ old('ends_on', $record['ends_on'] ?? '') }}" required>
                    @error('ends_on')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>

            <h2 class="h5">Bénéficiaire</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="beneficiary_name">Nom complet</label>
                    <input class="form-control" id="beneficiary_name" name="beneficiary_name" value="{{ old('beneficiary_name', $record['beneficiary_name'] ?? '') }}" required maxlength="255">
                    @error('beneficiary_name')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="member_number">Numéro de bénéficiaire</label>
                    <input class="form-control" id="member_number" name="member_number" value="{{ old('member_number', $record['member_number'] ?? '') }}" required maxlength="50">
                    @error('member_number')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="birth_date">Date de naissance</label>
                    <input class="form-control" type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', $record['birth_date'] ?? '') }}" required>
                    @error('birth_date')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="relationship">Lien avec l’assuré principal</label>
                    <select class="form-select" id="relationship" name="relationship" required>
                        @foreach (['self' => 'Assuré principal', 'spouse' => 'Conjoint(e)', 'child' => 'Enfant', 'other' => 'Autre'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('relationship', $record['relationship'] ?? 'self') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('relationship')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary" type="submit">{{ $beneficiary ? 'Enregistrer la correction' : 'Créer le dossier' }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('coverage-roster.index', ['insurer_id' => $insurer?->id]) }}">Annuler</a>
        </div>
    </form>
@endsection
