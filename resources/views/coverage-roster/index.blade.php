@extends('layouts.app')

@section('title', 'Portefeuille assuré')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                    <i class="bi bi-people-fill"></i>
                </span>
                <h1 class="h2 mb-0 fw-bold">Portefeuille assuré</h1>
            </div>
            <p class="text-body-secondary mb-0">Gérez les bénéficiaires et contrats de votre assureur.</p>
        </div>
        @if ($insurer)
            <a class="btn btn-primary shadow-sm" href="{{ route('coverage-roster.create', ['insurer_id' => $insurer->id]) }}">
                <i class="bi bi-person-plus-fill me-1"></i>
                Ajouter un bénéficiaire
            </a>
        @endif
    </div>

    @if (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
        <form method="GET" action="{{ route('coverage-roster.index') }}" class="card border-0 shadow-sm p-4 mb-4">
            <label for="insurer_id" class="form-label fw-bold">Assureur à administrer</label>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <select class="form-select flex-grow-1" name="insurer_id" id="insurer_id" required>
                    <option value="">Choisir un assureur</option>
                    @foreach ($insurers as $option)
                        <option value="{{ $option->id }}" @selected($insurer?->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-primary px-4" type="submit">
                    <i class="bi bi-eye me-1"></i>
                    Afficher
                </button>
            </div>
        </form>
    @endif

    @if ($insurer)
        <!-- CSV Import Card -->
        <section class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-spreadsheet text-primary"></i>
                <h2 class="h5 mb-0 fw-bold">Importer un fichier CSV · {{ $insurer->name }}</h2>
            </div>
            <div class="card-body p-4">
                <p class="text-body-secondary small mb-2">
                    Le fichier est d’abord vérifié. Rien n’est enregistré avant confirmation de l’aperçu.
                </p>
                <p class="mb-3">
                    <a class="text-decoration-none small fw-semibold" href="{{ route('coverage-roster.template') }}">
                        <i class="bi bi-download me-1"></i>
                        Télécharger le modèle CSV avec une ligne fictive
                    </a>
                </p>
                <form method="POST" action="{{ route('coverage-roster.import.preview') }}" enctype="multipart/form-data">
                    @csrf
                    @if (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
                        <input type="hidden" name="insurer_id" value="{{ $insurer->id }}">
                    @endif
                    <div class="mb-3">
                        <label class="form-label" for="csv_file">Fichier CSV (2 Mo maximum)</label>
                        <input class="form-control" type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required>
                        @error('csv_file')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-shield-check me-1"></i>
                        Vérifier avant import
                    </button>
                </form>
            </div>
        </section>

        <!-- Beneficiaries List -->
        <section aria-labelledby="roster-title">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 id="roster-title" class="h4 mb-0 fw-bold">Bénéficiaires enregistrés</h2>
                <span class="badge rounded-pill text-bg-secondary">{{ $beneficiaries->total() }} bénéficiaire(s)</span>
            </div>

            @if ($beneficiaries->isEmpty())
                <div class="alert alert-info shadow-sm d-flex align-items-center gap-2" role="status">
                    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
                    <div>Aucun bénéficiaire dans le portefeuille sélectionné.</div>
                </div>
            @else
                <div class="card border-0 shadow-sm overflow-hidden mb-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-3">Bénéficiaire</th>
                                    <th scope="col">N° bénéficiaire</th>
                                    <th scope="col">Assuré principal</th>
                                    <th scope="col">Souscripteur</th>
                                    <th scope="col">Contrats</th>
                                    <th scope="col" class="text-end pe-3"><span class="visually-hidden">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($beneficiaries as $beneficiary)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark">{{ $beneficiary->name }}</div>
                                            <div class="small text-muted">
                                                <i class="bi bi-calendar-event me-1"></i>{{ $beneficiary->birth_date?->format('d/m/Y') }}
                                            </div>
                                        </td>
                                        <td class="fw-semibold text-secondary">{{ $beneficiary->member_number }}</td>
                                        <td>{{ $beneficiary->policyholder->name }}</td>
                                        <td>{{ $beneficiary->policyholder->subscriber->name }}</td>
                                        <td>
                                            @foreach ($beneficiary->policyholder->subscriber->policies as $policy)
                                                <div class="small">
                                                    <span class="fw-semibold">{{ $policy->number }}</span>
                                                    <span class="text-muted">· {{ $policy->coverage_rate }} %</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="text-end pe-3">
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('coverage-roster.edit', $beneficiary) }}">
                                                <i class="bi bi-pencil me-1"></i>
                                                Corriger
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                {{ $beneficiaries->links('partials.pagination') }}
            @endif
        </section>
    @elseif (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
        <div class="alert alert-info shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
            <div>Choisissez un assureur pour consulter ou corriger son portefeuille.</div>
        </div>
    @endif
@endsection
