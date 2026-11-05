@extends('layouts.app')

@section('title', 'Comptes utilisateurs')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                    <i class="bi bi-people-fill"></i>
                </span>
                <h1 class="h2 mb-0 fw-bold">Comptes utilisateurs</h1>
            </div>
            <p class="text-body-secondary mb-0">Créez les comptes et attribuez un rôle et une organisation.</p>
        </div>
        <a class="btn btn-primary shadow-sm" href="{{ route('admin.users.create') }}">
            <i class="bi bi-person-plus-fill me-1"></i>
            Créer un compte
        </a>
    </div>

    @if ($users->isEmpty())
        <div class="alert alert-info shadow-sm d-flex align-items-center gap-2" role="status">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
            <div>Aucun compte utilisateur.</div>
        </div>
    @else
        <div class="card border-0 shadow-sm overflow-hidden mb-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Nom</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Rôle</th>
                            <th scope="col">Organisation</th>
                            <th scope="col">État</th>
                            <th scope="col" class="text-end pe-3"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <th scope="row" class="ps-3 fw-bold text-dark">
                                    <i class="bi bi-person-circle text-muted me-1"></i>
                                    {{ $user->name }}
                                </th>
                                <td class="text-secondary small">{{ $user->email }}</td>
                                <td>
                                    <span class="badge text-bg-light border text-dark">
                                        {{ $user->role->label() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($user->organization)
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <i class="bi {{ $user->organization->type === \App\Enums\OrganizationType::Facility ? 'bi-hospital' : 'bi-shield' }} text-muted small"></i>
                                            {{ $user->organization->name }}
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary">Plateforme</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge rounded-pill {{ $user->is_active ? 'text-bg-success' : 'text-bg-danger' }}">
                                        {{ $user->is_active ? 'Actif' : 'Désactivé' }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', $user) }}">
                                        <i class="bi bi-pencil me-1"></i>
                                        Modifier
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        {{ $users->links('partials.pagination') }}
    @endif
@endsection
