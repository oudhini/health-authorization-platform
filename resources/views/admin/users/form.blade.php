@extends('layouts.app')

@section('title', $managedUser ? 'Modifier un compte' : 'Créer un compte')

@section('content')
    <div class="mb-4">
        <nav aria-label="Fil d'Ariane" class="mb-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}" class="text-decoration-none">Comptes utilisateurs</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $managedUser ? 'Modifier' : 'Nouveau compte' }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                <i class="bi {{ $managedUser ? 'bi-person-gear' : 'bi-person-plus-fill' }}"></i>
            </span>
            <h1 class="h2 mb-0 fw-bold">{{ $managedUser ? 'Modifier le compte' : 'Créer un compte utilisateur' }}</h1>
        </div>
        <p class="text-body-secondary mt-1 mb-0">
            Un agent doit appartenir à un établissement et un gestionnaire à un assureur.
            L’administrateur plateforme n’est rattaché à aucune organisation.
        </p>
    </div>

    @error('request')
        <div class="alert alert-danger shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <form method="POST"
        action="{{ $managedUser ? route('admin.users.update', $managedUser) : route('admin.users.store') }}"
        class="card border-0 shadow-sm overflow-hidden">
        @csrf
        @if ($managedUser)
            @method('PUT')
        @endif

        <div class="card-body p-4 d-grid gap-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Nom</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                        value="{{ old('name', $managedUser?->name) }}" placeholder="Ex. Dr. Sophie Mbarga" maxlength="255" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                        type="email" value="{{ old('email', $managedUser?->email) }}" placeholder="sophie@etablissement.test" maxlength="255" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="role">Rôle</label>
                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $managedUser?->role->value) === $role->value)>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="organization_id">Organisation (laisser vide pour un administrateur)</label>
                    <select class="form-select @error('organization_id') is-invalid @enderror" id="organization_id"
                        name="organization_id">
                        <option value="">Aucune — plateforme</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}"
                                @selected((string) old('organization_id', $managedUser?->organization_id) === (string) $organization->id)>
                                {{ $organization->name }} · {{ $organization->type->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('organization_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 p-3 bg-light rounded-3 border">
                <div class="col-md-6">
                    <label class="form-label" for="password">
                        {{ $managedUser ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe initial' }}
                    </label>
                    <input class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                        type="password" minlength="12" autocomplete="new-password" @required(! $managedUser)>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text small text-muted mt-1">
                        Au moins 12 caractères. La réinitialisation par e-mail n’est pas disponible dans cette démo.
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation"
                        type="password" minlength="12" autocomplete="new-password" @required(! $managedUser)>
                </div>
            </div>

            <div class="form-check p-3 bg-light rounded-3 border">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input ms-0 me-2" id="is_active" name="is_active" type="checkbox" value="1"
                    @checked(old('is_active', $managedUser?->is_active ?? true))>
                <label class="form-check-label fw-semibold" for="is_active">Compte actif</label>
                @error('is_active')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex flex-wrap gap-2 pt-2">
                <button class="btn btn-primary shadow-sm px-4" type="submit">
                    <i class="bi bi-save me-1"></i>
                    Enregistrer
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">
                    Retour
                </a>
            </div>
        </div>
    </form>
@endsection
