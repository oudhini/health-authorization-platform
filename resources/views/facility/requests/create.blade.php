@extends('layouts.app')

@section('title', 'Nouvelle demande')

@section('content')
    <div class="mb-4">
        <nav aria-label="Fil d'Ariane" class="mb-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('facility.requests.index') }}" class="text-decoration-none">Mes demandes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nouvelle demande</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                <i class="bi bi-file-earmark-medical"></i>
            </span>
            <h1 class="h2 mb-0 fw-bold">Nouvelle demande de prise en charge</h1>
        </div>
        <p class="text-body-secondary mt-1 mb-0">
            Choisissez l'assureur, recherchez le bénéficiaire couvert, puis renseignez les actes.
        </p>
    </div>

    @if ($insurers->isEmpty())
        <div class="alert alert-warning d-flex align-items-center gap-2 shadow-sm" role="status">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
            <div>
                Aucun assureur partenaire n'est configuré pour votre établissement. Contactez l'administrateur de la plateforme.
            </div>
        </div>
    @else
        <!-- Step 1: Search Beneficiary -->
        <section class="card border-0 shadow-sm mb-4" aria-labelledby="beneficiary-search-heading">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                <span class="hap-step-number">1</span>
                <h2 class="h5 mb-0 fw-bold" id="beneficiary-search-heading">1. Rechercher la couverture du patient</h2>
            </div>
            <div class="card-body p-4">
                <p class="text-body-secondary small mb-3">
                    Choisissez l'assureur figurant sur les justificatifs du patient. La recherche ne montrera que
                    les bénéficiaires ayant une police active auprès de cet assureur.
                </p>

                <form method="GET" action="{{ route('facility.requests.create') }}" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label" for="insurer_id">Assureur</label>
                        <select class="form-select" id="insurer_id" name="insurer_id" required>
                            <option value="">— Choisir un assureur partenaire —</option>
                            @foreach ($insurers as $insurer)
                                <option value="{{ $insurer->id }}" @selected($selectedInsurer?->id === $insurer->id)>
                                    {{ $insurer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="search">Nom ou numéro de bénéficiaire</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input class="form-control border-start-0" id="search" name="search" type="search"
                                value="{{ $searchTerm }}" minlength="2" maxlength="100" autocomplete="off"
                                placeholder="Ex. Enfant Démo ou DEMO-ALPHA-0002" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 shadow-sm">
                            <i class="bi bi-search me-1"></i>
                            <span>Rechercher</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        @if ($selectedInsurer && $searchTerm !== '')
            @if (mb_strlen($searchTerm) < 2)
                <div class="alert alert-info shadow-sm d-flex align-items-center gap-2 mb-4" role="status">
                    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
                    <div>Saisissez au moins deux caractères pour lancer la recherche.</div>
                </div>
            @elseif ($beneficiaries->isEmpty())
                <div class="alert alert-secondary shadow-sm d-flex align-items-start gap-2 mb-4" role="status">
                    <i class="bi bi-person-x fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        Aucun bénéficiaire avec une couverture active n'a été trouvé pour {{ $selectedInsurer->name }}.
                        Vérifiez le nom, le numéro et l'assureur indiqués sur les justificatifs.
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('facility.requests.store') }}" class="card border-0 shadow-sm">
                    @csrf
                    <input type="hidden" name="insurer_id" value="{{ $selectedInsurer->id }}">

                    <!-- Step 2: Choose Beneficiary & Policy -->
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                        <span class="hap-step-number">2</span>
                        <h2 class="h5 mb-0 fw-bold">2. Choisir le bénéficiaire et sa couverture</h2>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-body-secondary small mb-3">
                            Vérifiez le nom, le numéro de bénéficiaire, le numéro de police et le taux avant de continuer.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="coverage_selection">Bénéficiaire et contrat</label>
                                <select class="form-select @error('coverage_selection') is-invalid @enderror"
                                    id="coverage_selection" name="coverage_selection" required>
                                    <option value="">— Sélectionner une couverture —</option>
                                    @foreach ($beneficiaries as $beneficiary)
                                        <optgroup label="{{ $beneficiary->name }} · {{ $beneficiary->member_number }}">
                                            @foreach ($beneficiary->policyholder->subscriber->policies as $policy)
                                                <option value="{{ $beneficiary->id }}:{{ $policy->id }}"
                                                    data-beneficiary-id="{{ $beneficiary->id }}"
                                                    @selected(old('coverage_selection') === $beneficiary->id.':'.$policy->id)>
                                                    Police {{ $policy->number }} · Couverture {{ $policy->coverage_rate }} %
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('coverage_selection')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="related_request_id">
                                    Consultation approuvée liée
                                    <span class="text-body-secondary fw-normal small">(facultatif, pour un examen prescrit)</span>
                                </label>
                                <select class="form-select @error('related_request_id') is-invalid @enderror"
                                    id="related_request_id" name="related_request_id" disabled>
                                    <option value="">Aucune</option>
                                    @foreach ($approvedRequests as $approved)
                                        <option value="{{ $approved->id }}"
                                            data-beneficiary-id="{{ $approved->beneficiary_id }}"
                                            hidden disabled
                                            @selected((string) old('related_request_id') === (string) $approved->id)>
                                            #{{ $approved->id }} · {{ $approved->beneficiary->name }}
                                            ({{ $approved->beneficiary->member_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('related_request_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text small mt-1 text-muted" id="related-request-help" role="status">
                                    Sélectionnez d'abord un bénéficiaire pour afficher ses consultations approuvées.
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Medical Acts -->
                        <div class="pt-3 border-top mt-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="hap-step-number">3</span>
                                <h2 class="h5 mb-0 fw-bold">3. Actes médicaux</h2>
                            </div>
                            <p class="text-body-secondary small mb-3">
                                Remplissez au moins une ligne. Les montants sont en FCFA, sans décimales.
                            </p>

                            <div class="table-responsive rounded-3 border">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="ps-3" style="width: 60%;">Acte médical</th>
                                            <th scope="col" class="pe-3" style="width: 40%;">Montant facturé (FCFA)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @for ($i = 0; $i < $rows; $i++)
                                            <tr>
                                                <td class="ps-3">
                                                    <label class="visually-hidden" for="act_{{ $i }}">Acte {{ $i + 1 }}</label>
                                                    <select class="form-select" id="act_{{ $i }}"
                                                        name="acts[{{ $i }}][medical_act_id]">
                                                        <option value="">— Choisir un acte —</option>
                                                        @foreach ($acts as $act)
                                                            <option value="{{ $act->id }}"
                                                                @selected((string) old("acts.$i.medical_act_id") === (string) $act->id)>
                                                                {{ $act->label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="pe-3">
                                                    <label class="visually-hidden" for="amount_{{ $i }}">
                                                        Montant facturé pour l'acte {{ $i + 1 }}
                                                    </label>
                                                    <div class="input-group">
                                                        <input class="form-control" id="amount_{{ $i }}"
                                                            name="acts[{{ $i }}][billed_amount]" type="number"
                                                            min="1" step="1" inputmode="numeric"
                                                            placeholder="0"
                                                            value="{{ old("acts.$i.billed_amount") }}">
                                                        <span class="input-group-text bg-light text-muted small">FCFA</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                            <button type="submit" class="btn btn-primary shadow-sm px-4">
                                <i class="bi bi-save me-1"></i>
                                <span>Créer le brouillon</span>
                            </button>
                            <a class="btn btn-outline-secondary px-3" href="{{ route('facility.requests.index') }}">
                                Annuler
                            </a>
                        </div>
                    </div>
                </form>
                @if ($hasMoreResults)
                    <p class="form-text mt-2 text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Plus de 20 résultats correspondent. Affinez la recherche avec le nom complet ou le numéro de bénéficiaire.
                    </p>
                @endif
            @endif
        @elseif ($selectedInsurer)
            <div class="alert alert-info shadow-sm d-flex align-items-center gap-2 mb-4" role="status">
                <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
                <div>
                    Saisissez au moins deux caractères du nom ou du numéro du bénéficiaire.
                </div>
            </div>
        @endif
    @endif
@endsection
