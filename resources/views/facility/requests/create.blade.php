@extends('layouts.app')

@section('title', 'Nouvelle demande')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Nouvelle demande de prise en charge</h1>
        <p class="text-body-secondary mb-0">
            Choisissez l'assureur, recherchez le bénéficiaire couvert, puis renseignez les actes.
        </p>
    </div>

    @if ($insurers->isEmpty())
        <div class="alert alert-warning" role="status">
            Aucun assureur partenaire n'est configuré pour votre établissement. Contactez l'administrateur de la plateforme.
        </div>
    @else
        <section class="card shadow-sm mb-4" aria-labelledby="beneficiary-search-heading">
            <div class="card-body p-4">
                <h2 class="h5" id="beneficiary-search-heading">1. Rechercher la couverture du patient</h2>
                <p class="text-body-secondary">
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
                        <input class="form-control" id="search" name="search" type="search"
                            value="{{ $searchTerm }}" minlength="2" maxlength="100" autocomplete="off"
                            placeholder="Ex. Enfant Démo ou DEMO-ALPHA-0002" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            Rechercher
                        </button>
                    </div>
                </form>
            </div>
        </section>

        @if ($selectedInsurer && $searchTerm !== '')
            @if (mb_strlen($searchTerm) < 2)
                <div class="alert alert-info" role="status">Saisissez au moins deux caractères pour lancer la recherche.</div>
            @elseif ($beneficiaries->isEmpty())
                <div class="alert alert-secondary" role="status">
                    Aucun bénéficiaire avec une couverture active n'a été trouvé pour {{ $selectedInsurer->name }}.
                    Vérifiez le nom, le numéro et l'assureur indiqués sur les justificatifs.
                </div>
            @else
                <form method="POST" action="{{ route('facility.requests.store') }}" class="card shadow-sm">
                    @csrf
                    <input type="hidden" name="insurer_id" value="{{ $selectedInsurer->id }}">
                    <div class="card-body p-4">
                        <h2 class="h5">2. Choisir le bénéficiaire et sa couverture</h2>
                        <p class="text-body-secondary">
                            Vérifiez le nom, le numéro de bénéficiaire, le numéro de police et le taux avant de continuer.
                        </p>
                        <div class="mb-4">
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

                        <div class="mb-4">
                            <label class="form-label" for="related_request_id">
                                Consultation approuvée liée
                                <span class="text-body-secondary fw-normal">(facultatif, pour un examen prescrit)</span>
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
                            <div class="form-text" id="related-request-help" role="status">
                                Sélectionnez d'abord un bénéficiaire pour afficher ses consultations approuvées.
                            </div>
                        </div>

                        <h2 class="h5">3. Actes médicaux</h2>
                        <p class="text-body-secondary">
                            Remplissez au moins une ligne. Les montants sont en FCFA, sans décimales.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Acte médical</th>
                                        <th scope="col">Montant facturé (FCFA)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for ($i = 0; $i < $rows; $i++)
                                        <tr>
                                            <td>
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
                                            <td>
                                                <label class="visually-hidden" for="amount_{{ $i }}">
                                                    Montant facturé pour l'acte {{ $i + 1 }}
                                                </label>
                                                <input class="form-control" id="amount_{{ $i }}"
                                                    name="acts[{{ $i }}][billed_amount]" type="number"
                                                    min="1" step="1" inputmode="numeric"
                                                    value="{{ old("acts.$i.billed_amount") }}">
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">Créer le brouillon</button>
                            <a class="btn btn-outline-secondary" href="{{ route('facility.requests.index') }}">Annuler</a>
                        </div>
                    </div>
                </form>
                @if ($hasMoreResults)
                    <p class="form-text mt-2">
                        Plus de 20 résultats correspondent. Affinez la recherche avec le nom complet ou le numéro de bénéficiaire.
                    </p>
                @endif
            @endif
        @elseif ($selectedInsurer)
            <div class="alert alert-info" role="status">
                Saisissez au moins deux caractères du nom ou du numéro du bénéficiaire.
            </div>
        @endif
    @endif
@endsection
