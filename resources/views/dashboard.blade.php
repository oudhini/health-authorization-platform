@extends('layouts.app')

@section('title', 'Accueil')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Bonjour {{ $user->name }}</h1>
        <p class="text-body-secondary mb-0">Votre espace de travail sécurisé.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p>
                Vous êtes connecté en tant que <strong>{{ $user->role->label() }}</strong>
                @if ($user->organization)
                    pour <strong>{{ $user->organization->name }}</strong>
                @endif.
            </p>

            <div class="d-flex flex-wrap gap-2">
                @if (Route::has('facility.requests.index') && $user->role === \App\Enums\UserRole::FacilityAgent)
                    <a class="btn btn-primary" href="{{ route('facility.requests.create') }}">Nouvelle demande</a>
                    <a class="btn btn-outline-primary" href="{{ route('facility.requests.index') }}">Mes demandes</a>
                @endif

                @if (Route::has('insurer.requests.index') && $user->role === \App\Enums\UserRole::InsurerManager)
                    <a class="btn btn-primary" href="{{ route('insurer.requests.index') }}">Demandes reçues</a>
                @endif

                @if ($user->role === \App\Enums\UserRole::PlatformAdmin)
                    <a class="btn btn-primary" href="{{ route('admin.audit-logs.index') }}">Journal d'audit</a>
                    <a class="btn btn-outline-primary" href="{{ route('admin.facility-insurers.index') }}">
                        Assureurs partenaires
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection
