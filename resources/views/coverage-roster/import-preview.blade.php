@extends('layouts.app')

@section('title', 'Vérification de l’import CSV')

@section('content')
    <div class="mb-4">
        <nav aria-label="Fil d'Ariane" class="mb-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('coverage-roster.index', ['insurer_id' => $insurer->id]) }}" class="text-decoration-none">Portefeuille assuré</a></li>
                <li class="breadcrumb-item active" aria-current="page">Vérification de l'import</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                <i class="bi bi-file-earmark-spreadsheet"></i>
            </span>
            <h1 class="h2 mb-0 fw-bold">Vérification de l’import CSV</h1>
        </div>
        <p class="text-body-secondary mt-1 mb-0">
            Assureur : <strong>{{ $insurer->name }}</strong>. Aucun changement n’a encore été enregistré.
        </p>
    </div>

    @if ($preview['errors'] !== [])
        <div class="alert alert-danger shadow-sm d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
            <div>
                <h2 class="h5 mb-2 fw-bold">Corrigez le fichier avant de continuer</h2>
                <ul class="mb-0 ps-3">
                    @foreach ($preview['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('coverage-roster.index', ['insurer_id' => $insurer->id]) }}">
            &larr; Revenir au portefeuille
        </a>
    @else
        <div class="alert alert-info shadow-sm d-flex align-items-center gap-2 mb-4" role="status">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
            <div>
                <strong>{{ $preview['creates'] }} nouveau(x) bénéficiaire(s)</strong>, <strong>{{ $preview['updates'] }} dossier(s) existant(s)</strong> à mettre à jour.
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h3 class="h6 mb-0 fw-bold text-dark">Aperçu des lignes du fichier</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Ligne</th>
                            <th scope="col">Bénéficiaire</th>
                            <th scope="col">N°</th>
                            <th scope="col">Contrat</th>
                            <th scope="col" class="pe-3">Taux</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['rows'] as $index => $row)
                            <tr>
                                <td class="ps-3 text-muted small">Ligne {{ $index + 2 }}</td>
                                <td class="fw-semibold text-dark">{{ $row['beneficiary_name'] }}</td>
                                <td class="text-secondary">{{ $row['member_number'] }}</td>
                                <td>{{ $row['policy_number'] }}</td>
                                <td class="pe-3 fw-bold text-primary">{{ $row['coverage_rate'] }} %</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('coverage-roster.import') }}" enctype="multipart/form-data" class="card border-0 shadow-sm p-4">
            @csrf
            @if (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
                <input type="hidden" name="insurer_id" value="{{ $insurer->id }}">
            @endif
            <input type="hidden" name="fingerprint" value="{{ $fingerprint }}">

            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-shield-check text-success fs-5"></i>
                <h3 class="h6 mb-0 fw-bold">Confirmation d'import sécurisé</h3>
            </div>
            <p class="text-body-secondary small mb-3">
                Pour confirmer, sélectionnez à nouveau le même fichier. Il sera revérifié avant l’enregistrement.
            </p>

            <div class="mb-4">
                <label class="form-label" for="csv_file">Fichier CSV revérifié</label>
                <input class="form-control" type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required>
                @error('csv_file')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary shadow-sm px-4" type="submit">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i>
                    Confirmer l’import
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('coverage-roster.index', ['insurer_id' => $insurer->id]) }}">
                    Annuler
                </a>
            </div>
        </form>
    @endif
@endsection
