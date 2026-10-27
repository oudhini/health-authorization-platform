@extends('layouts.app')

@section('title', 'Organisations')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Organisations</h1>
        <p class="text-body-secondary mb-0">
            Les organisations référencées sont conservées pour préserver les demandes et les traces d’audit.
            Leur type ne peut pas être modifié après création.
        </p>
    </div>

    <section class="card shadow-sm mb-4" aria-labelledby="create-organization-title">
        <div class="card-body">
            <h2 class="h5" id="create-organization-title">Ajouter une organisation</h2>
            <form method="POST" action="{{ route('admin.organizations.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="name">Nom</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="type">Type</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="">— Choisir —</option>
                        @foreach (\App\Enums\OrganizationType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Ajouter</button></div>
            </form>
        </div>
    </section>

    @if ($organizations->isEmpty())
        <div class="alert alert-info" role="status">Aucune organisation.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr><th scope="col">Type</th><th scope="col">Nom</th><th scope="col">Comptes</th><th scope="col">Renommer</th></tr>
                </thead>
                <tbody>
                    @foreach ($organizations as $organization)
                        <tr>
                            <td>{{ $organization->type->label() }}</td>
                            <td>{{ $organization->name }}</td>
                            <td>{{ $organization->users_count }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.organizations.update', $organization) }}"
                                    class="d-flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <label class="visually-hidden" for="org_{{ $organization->id }}">Nom de l’organisation</label>
                                    <input class="form-control" id="org_{{ $organization->id }}" name="name"
                                        value="{{ $organization->name }}" maxlength="255" required>
                                    <button class="btn btn-outline-primary" type="submit">Enregistrer</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $organizations->links('partials.pagination') }}
    @endif
@endsection
