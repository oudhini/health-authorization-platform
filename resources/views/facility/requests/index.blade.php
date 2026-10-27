@extends('layouts.app')

@section('title', 'Mes demandes')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Mes demandes</h1>
            <p class="text-body-secondary mb-0">Suivez les demandes de votre établissement.</p>
        </div>

        <a class="btn btn-primary no-print" href="{{ route('facility.requests.create') }}">Nouvelle demande</a>
    </div>

    @if ($requests->isEmpty())
        <div class="card shadow-sm">
            <p class="card-body text-center text-body-secondary mb-0">
                Aucune demande pour le moment. Créez votre première demande de prise en charge.
            </p>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th scope="col">N°</th>
                        <th scope="col">Bénéficiaire</th>
                        <th scope="col">Assureur</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Créée le</th>
                        <th scope="col"><span class="visually-hidden">Action</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($requests as $item)
                        <tr>
                            <td>#{{ $item->id }}</td>
                            <td>{{ $item->beneficiary->name }}</td>
                            <td>{{ $item->insurer->name }}</td>
                            <td>@include('partials.status-badge', ['status' => $item->status])</td>
                            <td>{{ $item->created_at->format('d/m/Y') }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('facility.requests.show', $item) }}">Ouvrir</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            @include('partials.pagination', ['paginator' => $requests])
        </div>
    @endif
@endsection
