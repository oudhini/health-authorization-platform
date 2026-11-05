@extends('layouts.app')

@section('title', 'Demandes reçues')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                    <i class="bi bi-inbox-fill"></i>
                </span>
                <h1 class="h2 mb-0 fw-bold">Demandes reçues</h1>
            </div>
            <p class="text-body-secondary mb-0">Demandes transmises à votre assureur.</p>
        </div>
    </div>

    <!-- Filter Pills / Tabs -->
    <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Filtrer par statut">
        <a class="btn {{ $filter === null ? 'btn-primary shadow-sm' : 'btn-outline-primary' }}"
            href="{{ route('insurer.requests.index') }}">
            <i class="bi bi-collection me-1"></i>
            Toutes
        </a>
        @foreach ($statuses as $status)
            <a class="btn {{ $filter === $status ? 'btn-primary shadow-sm' : 'btn-outline-primary' }}"
                href="{{ route('insurer.requests.index', ['status' => $status->value]) }}">
                {{ $status->label() }}
            </a>
        @endforeach
    </nav>

    @if ($requests->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body p-4">
                <div class="hap-brand-icon mx-auto mb-3 bg-light text-muted" style="width: 60px; height: 60px; font-size: 1.75rem;">
                    <i class="bi bi-inbox"></i>
                </div>
                <h2 class="h5 fw-bold text-dark mb-1">Aucune demande à afficher</h2>
                <p class="text-body-secondary mb-0">Les nouvelles demandes apparaîtront ici après leur envoi.</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">N°</th>
                            <th scope="col">Établissement</th>
                            <th scope="col">Bénéficiaire</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Envoyée le</th>
                            <th scope="col" class="text-end pe-3"><span class="visually-hidden">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $item)
                            <tr>
                                <td class="ps-3 fw-bold text-primary">#{{ $item->id }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->facility->name }}</div>
                                    <div class="small text-muted"><i class="bi bi-hospital me-1"></i>Conventionné</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->beneficiary->name }}</div>
                                    <div class="small text-muted">
                                        <i class="bi bi-person-badge me-1"></i>{{ $item->beneficiary->member_number }}
                                    </div>
                                </td>
                                <td>@include('partials.status-badge', ['status' => $item->status])</td>
                                <td class="text-secondary small">{{ $item->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-end pe-3">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('insurer.requests.show', $item) }}">
                                        <span>Ouvrir</span>
                                        <i class="bi bi-chevron-right ms-1"></i>
                                    </a>
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
