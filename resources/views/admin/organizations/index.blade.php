@extends('layouts.app')

@section('title', 'Organisations')

@section('content')
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                <i class="bi bi-buildings"></i>
            </span>
            <h1 class="h2 mb-0 fw-bold">Organisations</h1>
        </div>
        <p class="text-body-secondary mb-0">
            Les organisations référencées sont conservées pour préserver les demandes et les traces d’audit.
            Leur type ne peut pas être modifié après création.
        </p>
    </div>

    <!-- Add Organization Form -->
    <section class="card border-0 shadow-sm mb-4 overflow-hidden" aria-labelledby="create-organization-title">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle text-primary"></i>
            <h2 class="h5 mb-0 fw-bold" id="create-organization-title">Ajouter une organisation</h2>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.organizations.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="name">Nom</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Ex. Clinique Pasteur" maxlength="255" required>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="type">Type</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="">— Choisir —</option>
                        @foreach (\App\Enums\OrganizationType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 shadow-sm" type="submit">
                        <i class="bi bi-plus-lg me-1"></i>
                        Ajouter
                    </button>
                </div>
            </form>
        </div>
    </section>

    @if ($organizations->isEmpty())
        <div class="alert alert-info shadow-sm d-flex align-items-center gap-2" role="status">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
            <div>Aucune organisation.</div>
        </div>
    @else
        <div class="card border-0 shadow-sm overflow-hidden mb-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Type</th>
                            <th scope="col">Nom</th>
                            <th scope="col">Comptes</th>
                            <th scope="col" class="pe-3">Renommer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($organizations as $organization)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge text-bg-light border text-dark">
                                        <i class="bi {{ $organization->type === \App\Enums\OrganizationType::Facility ? 'bi-hospital' : 'bi-shield-check' }} me-1"></i>
                                        {{ $organization->type->label() }}
                                    </span>
                                </td>
                                <td class="fw-bold text-dark">{{ $organization->name }}</td>
                                <td>
                                    <span class="badge rounded-pill text-bg-secondary">
                                        {{ $organization->users_count }}
                                    </span>
                                </td>
                                <td class="pe-3">
                                    <form method="POST" action="{{ route('admin.organizations.update', $organization) }}"
                                        class="d-flex gap-2">
                                        @csrf
                                        @method('PUT')
                                        <label class="visually-hidden" for="org_{{ $organization->id }}">Nom de l’organisation</label>
                                        <input class="form-control form-control-sm" id="org_{{ $organization->id }}" name="name"
                                            value="{{ $organization->name }}" maxlength="255" required>
                                        <button class="btn btn-outline-primary btn-sm px-3" type="submit">
                                            Enregistrer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        {{ $organizations->links('partials.pagination') }}
    @endif
@endsection
