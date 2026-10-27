@extends('layouts.app')

@section('title', 'Comptes utilisateurs')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Comptes utilisateurs</h1>
            <p class="text-body-secondary mb-0">Créez les comptes et attribuez un rôle et une organisation.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.users.create') }}">Créer un compte</a>
    </div>

    @if ($users->isEmpty())
        <div class="alert alert-info" role="status">Aucun compte utilisateur.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Rôle</th>
                        <th scope="col">Organisation</th>
                        <th scope="col">État</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <th scope="row">{{ $user->name }}</th>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role->label() }}</td>
                            <td>{{ $user->organization?->name ?? 'Plateforme' }}</td>
                            <td>{{ $user->is_active ? 'Actif' : 'Désactivé' }}</td>
                            <td><a href="{{ route('admin.users.edit', $user) }}">Modifier</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links('partials.pagination') }}
    @endif
@endsection
