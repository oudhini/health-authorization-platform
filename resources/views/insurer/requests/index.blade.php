@extends('layouts.app')

@section('title', 'Demandes reçues')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Demandes reçues</h1>
            <p class="text-body-secondary mb-0">Demandes transmises à votre assureur.</p>
        </div>
    </div>

    <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Filtrer par statut">
        <a class="btn {{ $filter === null ? 'btn-primary' : 'btn-outline-primary' }}"
            href="{{ route('insurer.requests.index') }}">Toutes</a>
        @foreach ($statuses as $status)
            <a class="btn {{ $filter === $status ? 'btn-primary' : 'btn-outline-primary' }}"
                href="{{ route('insurer.requests.index', ['status' => $status->value]) }}">
                {{ $status->label() }}
            </a>
        @endforeach
    </nav>

    @if ($requests->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body py-5 text-center">
                <h2 class="h5">Aucune demande à afficher</h2>
                <p class="text-body-secondary mb-0">Les nouvelles demandes apparaîtront ici après leur envoi.</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">N°</th>
                            <th scope="col">Établissement</th>
                            <th scope="col">Bénéficiaire</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Envoyée le</th>
                            <th scope="col"><span class="visually-hidden">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $item)
                            <tr>
                                <td>#{{ $item->id }}</td>
                                <td>{{ $item->facility->name }}</td>
                                <td>{{ $item->beneficiary->name }}</td>
                                <td>@include('partials.status-badge', ['status' => $item->status])</td>
                                <td>{{ $item->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('insurer.requests.show', $item) }}">Ouvrir</a>
                                </td>
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
