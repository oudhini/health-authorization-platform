@extends('layouts.app')

@section('title', $managedUser ? 'Modifier un compte' : 'Créer un compte')

@section('content')
    <h1 class="h2 mb-1">{{ $managedUser ? 'Modifier le compte' : 'Créer un compte utilisateur' }}</h1>
    <p class="text-body-secondary mb-4">
        Un agent doit appartenir à un établissement et un gestionnaire à un assureur.
        L’administrateur plateforme n’est rattaché à aucune organisation.
    </p>

    <form method="POST"
        action="{{ $managedUser ? route('admin.users.update', $managedUser) : route('admin.users.store') }}"
        class="card shadow-sm">
        @csrf
        @if ($managedUser)
            @method('PUT')
        @endif
        <div class="card-body d-grid gap-3">
            <div>
                <label class="form-label" for="name">Nom</label>
                <input class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                    value="{{ old('name', $managedUser?->name) }}" maxlength="255" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="email">Adresse e-mail</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                    type="email" value="{{ old('email', $managedUser?->email) }}" maxlength="255" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
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
            <div>
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
            <div>
                <label class="form-label" for="password">
                    {{ $managedUser ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe initial' }}
                </label>
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                    type="password" minlength="12" autocomplete="new-password" @required(! $managedUser)>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Au moins 12 caractères. La réinitialisation par e-mail n’est pas disponible dans cette démo.</div>
            </div>
            <div>
                <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation"
                    type="password" minlength="12" autocomplete="new-password" @required(! $managedUser)>
            </div>
            <div class="form-check">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1"
                    @checked(old('is_active', $managedUser?->is_active ?? true))>
                <label class="form-check-label" for="is_active">Compte actif</label>
                @error('is_active')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            @error('request')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Enregistrer</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Retour</a>
            </div>
        </div>
    </form>
@endsection
