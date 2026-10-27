@extends('layouts.app')

@section('title', 'Journal d\'audit')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Journal d'audit</h1>
        <p class="text-body-secondary mb-0">Actions sensibles enregistrées par la plateforme.</p>
    </div>

    @if ($logs->isEmpty())
        <div class="card shadow-sm">
            <p class="card-body text-center text-body-secondary mb-0">Aucune action enregistrée pour le moment.</p>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <caption class="caption-top px-3 pt-3 text-body-secondary">
                        Actions sensibles, de la plus récente à la plus ancienne.
                    </caption>
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Utilisateur</th>
                        <th scope="col">Action</th>
                        <th scope="col">Objet</th>
                        <th scope="col">Détails</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? '—' }}</td>
                            <td><code>{{ $log->action->value }}</code></td>
                            <td>@if ($log->subject_type){{ class_basename($log->subject_type) }} #{{ $log->subject_id }}@else — @endif</td>
                            <td class="text-break">@if ($log->metadata){{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE) }}@else — @endif</td>
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
