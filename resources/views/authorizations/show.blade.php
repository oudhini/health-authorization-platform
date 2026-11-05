@extends('layouts.app')

@section('title', 'Bon '.$authorization->number)

@section('content')
    @use('App\Support\Fcfa')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 no-print">
        <a class="btn btn-outline-secondary" href="javascript:history.back()">
            <i class="bi bi-arrow-left me-1"></i>
            Retour
        </a>
        <div class="d-flex gap-2">
            <a class="btn btn-primary shadow-sm" href="{{ route('authorizations.pdf', $authorization) }}">
                <i class="bi bi-file-earmark-pdf me-1"></i>
                Télécharger le bon en PDF
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>
                Imprimer cette page
            </button>
        </div>
    </div>

    <!-- Official Guarantee Document Container -->
    <article class="card border-0 shadow-md p-4 p-lg-5 bg-white position-relative overflow-hidden mb-5">
        <!-- Subtle medical stamp/seal background on screen -->
        <div class="position-absolute top-0 end-0 p-4 opacity-10 no-print" style="pointer-events: none;">
            <i class="bi bi-shield-check" style="font-size: 8rem; color: #0f4c81;"></i>
        </div>

        <header class="d-flex flex-column flex-md-row justify-content-between align-items-start border-bottom pb-4 mb-4 gap-3">
            <div>
                <span class="badge text-bg-primary rounded-pill px-3 py-1 mb-2">Document officiel</span>
                <h1 class="h2 fw-bold text-dark mb-1">Bon de prise en charge {{ $authorization->number }}</h1>
                <p class="text-body-secondary small mb-0">Document fictif de développement, sans valeur réelle.</p>
            </div>
            <div class="text-md-end">
                @if ($validity === 'expired')
                    <div class="alert alert-danger py-2 px-3 mb-0 d-inline-flex align-items-center gap-2" role="status">
                        <i class="bi bi-x-circle-fill"></i>
                        <span>Ce bon est expiré.</span>
                    </div>
                @elseif ($validity === 'not_started')
                    <div class="alert alert-warning py-2 px-3 mb-0 d-inline-flex align-items-center gap-2" role="status">
                        <i class="bi bi-clock-fill"></i>
                        <span>Ce bon n'est pas encore valable.</span>
                    </div>
                @else
                    <div class="alert alert-success py-2 px-3 mb-0 d-inline-flex align-items-center gap-2" role="status">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Ce bon est valable.</span>
                    </div>
                @endif
            </div>
        </header>

        <!-- Guarantee Info Grid -->
        <div class="card bg-light border p-4 rounded-3 mb-4">
            <div class="row g-4">
                <dl class="col-md-4 mb-0">
                    <dt class="text-muted small text-uppercase fw-semibold mb-1">Assureur</dt>
                    <dd class="fw-bold text-dark fs-6 mb-3">{{ $authorizationRequest->insurer->name }}</dd>

                    <dt class="text-muted small text-uppercase fw-semibold mb-1">Établissement</dt>
                    <dd class="fw-bold text-dark fs-6 mb-3">{{ $authorizationRequest->facility->name }}</dd>

                    <dt class="text-muted small text-uppercase fw-semibold mb-1">Numéro de police</dt>
                    <dd class="fw-semibold text-primary mb-0">{{ $authorizationRequest->policy->number }}</dd>
                </dl>

                <dl class="col-md-4 mb-0">
                    <dt class="text-muted small text-uppercase fw-semibold mb-1">Bénéficiaire</dt>
                    <dd class="fw-bold text-dark fs-6 mb-3">{{ $authorizationRequest->beneficiary->name }}</dd>

                    <dt class="text-muted small text-uppercase fw-semibold mb-1">Numéro du bénéficiaire</dt>
                    <dd class="fw-semibold text-secondary mb-3">{{ $authorizationRequest->beneficiary->member_number }}</dd>

                    <dt class="text-muted small text-uppercase fw-semibold mb-1">Assuré principal</dt>
                    <dd class="fw-semibold text-dark mb-0">{{ $authorizationRequest->beneficiary->policyholder->name }}</dd>
                </dl>

                <dl class="col-md-4 mb-0">
                    <dt class="text-muted small text-uppercase fw-semibold mb-1">Taux de prise en charge</dt>
                    <dd class="fw-bold text-success fs-5 mb-3">{{ $authorizationRequest->coverage_rate }} %</dd>

                    <dt class="text-muted small text-uppercase fw-semibold mb-1">Période de validité</dt>
                    <dd class="fw-semibold text-dark mb-0">
                        Valable du <strong>{{ $authorization->valid_from->format('d/m/Y') }}</strong> au <strong>{{ $authorization->valid_until->format('d/m/Y') }}</strong>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Medical Acts Table -->
        <h2 class="h4 fw-bold mb-3">Actes pris en charge</h2>
        <div class="table-responsive rounded-3 border mb-4">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-3">Acte</th>
                        <th scope="col" class="text-end text-nowrap">Facturé</th>
                        <th scope="col" class="text-end text-nowrap">Tarif officiel</th>
                        <th scope="col" class="text-end text-nowrap text-primary">Part assureur</th>
                        <th scope="col" class="text-end text-nowrap pe-3">Reste à charge</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($authorizationRequest->items as $item)
                        <tr>
                            <td class="ps-3 fw-medium">
                                <i class="bi bi-activity text-primary me-2"></i>
                                {{ $item->medicalAct->label }}
                            </td>
                            <td class="text-end text-nowrap tabular-nums">{{ Fcfa::format($item->billed_amount) }}</td>
                            <td class="text-end text-nowrap tabular-nums text-muted">{{ Fcfa::format($item->official_tariff) }}</td>
                            <td class="text-end text-nowrap tabular-nums text-primary fw-bold">{{ Fcfa::format($item->insurer_share) }}</td>
                            <td class="text-end text-nowrap tabular-nums pe-3 fw-semibold">{{ Fcfa::format($item->patient_share) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold border-top">
                    <tr>
                        <th scope="row" class="ps-3">Total</th>
                        <td class="text-end text-nowrap tabular-nums">{{ Fcfa::format($authorization->billed_total) }}</td>
                        <td class="text-end text-muted">—</td>
                        <td class="text-end text-nowrap tabular-nums text-primary fs-6">{{ Fcfa::format($authorization->insurer_total) }}</td>
                        <td class="text-end text-nowrap tabular-nums pe-3 fs-6">{{ Fcfa::format($authorization->patient_total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="p-3 bg-light rounded-3 border small text-secondary">
            <p class="mb-1">
                <i class="bi bi-info-circle me-1 text-primary"></i>
                Seuls les actes mentionnés ci-dessus sont pris en charge.
                @if ($authorization->overrun_total > 0)
                    Le reste à charge comprend un dépassement de
                    <strong>{{ Fcfa::format($authorization->overrun_total) }}</strong> par rapport aux tarifs officiels.
                @endif
            </p>
        </div>
    </article>
@endsection
