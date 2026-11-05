@extends('layouts.app')

@section('title', 'Mes demandes')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                    <i class="bi bi-hospital"></i>
                </span>
                <h1 class="h2 mb-0 fw-bold">Mes demandes</h1>
            </div>
            <p class="text-body-secondary mb-0">Suivez les demandes de votre établissement.</p>
        </div>

        <a class="btn btn-primary no-print shadow-sm" href="{{ route('facility.requests.create') }}">
            <i class="bi bi-plus-circle me-1"></i>
            <span>Nouvelle demande</span>
        </a>
    </div>

    @if ($requests->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body p-4">
                <div class="hap-brand-icon mx-auto mb-3 bg-light text-muted" style="width: 60px; height: 60px; font-size: 1.75rem;">
                    <i class="bi bi-folder2-open"></i>
                </div>
                <h2 class="h5 text-dark fw-bold mb-2">Aucune demande enregistrée</h2>
                <p class="text-body-secondary mb-4 mx-auto" style="max-width: 480px;">
                    Aucune demande pour le moment. Créez votre première demande de prise en charge.
                </p>
                <a class="btn btn-primary shadow-sm" href="{{ route('facility.requests.create') }}">
                    <i class="bi bi-plus-circle me-1"></i>
                    <span>Créer une demande</span>
                </a>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">N°</th>
                            <th scope="col">Bénéficiaire</th>
                            <th scope="col">Assureur</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Créée le</th>
                            <th scope="col" class="text-end pe-3"><span class="visually-hidden">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $item)
                            <tr>
                                <td class="ps-3 fw-bold text-primary">#{{ $item->id }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->beneficiary->name }}</div>
                                    <div class="small text-muted">
                                        <i class="bi bi-person-badge me-1"></i>{{ $item->beneficiary->member_number }}
                                    </div>
                                </td>
                                <td>
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-shield-check text-muted"></i>
                                        <span>{{ $item->insurer->name }}</span>
                                    </span>
                                </td>
                                <td>@include('partials.status-badge', ['status' => $item->status])</td>
                                <td class="text-secondary small">{{ $item->created_at->format('d/m/Y') }}</td>
                                <td class="text-end pe-3">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('facility.requests.show', $item) }}">
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
