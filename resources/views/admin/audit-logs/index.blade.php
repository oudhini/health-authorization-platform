@extends('layouts.app')

@section('title', 'Journal d\'audit')

@section('content')
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
            <h1 class="h2 mb-0 fw-bold">Journal d'audit</h1>
        </div>
        <p class="text-body-secondary mb-0">Actions sensibles enregistrées par la plateforme.</p>
    </div>

    @if ($logs->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body p-4">
                <i class="bi bi-journal-x fs-1 text-muted d-block mb-2"></i>
                <p class="text-body-secondary mb-0">Aucune action enregistrée pour le moment.</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm overflow-hidden mb-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <caption class="caption-top px-4 pt-3 pb-2 text-body-secondary small">
                        <i class="bi bi-info-circle me-1"></i>
                        Actions sensibles, de la plus récente à la plus ancienne.
                    </caption>
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Date</th>
                            <th scope="col">Utilisateur</th>
                            <th scope="col">Action</th>
                            <th scope="col">Objet</th>
                            <th scope="col" class="pe-4">Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="ps-4 text-nowrap small text-secondary">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <i class="bi bi-person me-1 text-muted"></i>
                                        {{ $log->user?->name ?? '—' }}
                                    </div>
                                </td>
                                <td>
                                    <code class="px-2 py-1 bg-light rounded text-primary fw-semibold small">
                                        {{ $log->action->value }}
                                    </code>
                                </td>
                                <td>
                                    @if ($log->subject_type)
                                        <span class="badge text-bg-light border text-dark">
                                            {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-break small">
                                    @if ($log->metadata)
                                        <code class="text-muted small">{{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE) }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            @include('partials.pagination', ['paginator' => $logs])
        </div>
    @endif
@endsection
