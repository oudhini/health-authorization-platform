@extends('layouts.app')

@section('title', 'Bon '.$authorization->number)

@section('content')
    @use('App\Support\Fcfa')

    <div class="d-flex justify-content-end mb-3 no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            Imprimer ou enregistrer en PDF
        </button>
    </div>

    <article>
        <header class="mb-4">
            <h1 class="h2">Bon de prise en charge {{ $authorization->number }}</h1>
            <p class="text-body-secondary">Document fictif de développement, sans valeur réelle.</p>
        </header>

        @if ($validity === 'expired')
            <div class="alert alert-danger" role="status">Ce bon est expiré.</div>
        @elseif ($validity === 'not_started')
            <div class="alert alert-warning" role="status">Ce bon n'est pas encore valable.</div>
        @else
            <div class="alert alert-success" role="status">Ce bon est valable.</div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <dl class="col-md-4 mb-0">
                        <dt>Assureur</dt>
                        <dd>{{ $authorizationRequest->insurer->name }}</dd>
                        <dt>Établissement</dt>
                        <dd>{{ $authorizationRequest->facility->name }}</dd>
                        <dt>Numéro de police</dt>
                        <dd>{{ $authorizationRequest->policy->number }}</dd>
                    </dl>
                    <dl class="col-md-4 mb-0">
                        <dt>Bénéficiaire</dt>
                        <dd>{{ $authorizationRequest->beneficiary->name }}</dd>
                        <dt>Numéro du bénéficiaire</dt>
                        <dd>{{ $authorizationRequest->beneficiary->member_number }}</dd>
                        <dt>Assuré principal</dt>
                        <dd>{{ $authorizationRequest->beneficiary->policyholder->name }}</dd>
                    </dl>
                    <dl class="col-md-4 mb-0">
                        <dt>Taux de prise en charge</dt>
                        <dd>{{ $authorizationRequest->coverage_rate }} %</dd>
                        <dt>Valable du</dt>
                        <dd>{{ $authorization->valid_from->format('d/m/Y') }} au {{ $authorization->valid_until->format('d/m/Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <h2 class="h4">Actes pris en charge</h2>
        <div class="table-responsive mb-3">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Acte</th>
                        <th scope="col" class="text-end text-nowrap">Facturé</th>
                        <th scope="col" class="text-end text-nowrap">Tarif officiel</th>
                        <th scope="col" class="text-end text-nowrap">Part assureur</th>
                        <th scope="col" class="text-end text-nowrap">Reste à charge</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($authorizationRequest->items as $item)
                        <tr>
                            <td>{{ $item->medicalAct->label }}</td>
                            <td class="text-end text-nowrap">{{ Fcfa::format($item->billed_amount) }}</td>
                            <td class="text-end text-nowrap">{{ Fcfa::format($item->official_tariff) }}</td>
                            <td class="text-end text-nowrap">{{ Fcfa::format($item->insurer_share) }}</td>
                            <td class="text-end text-nowrap">{{ Fcfa::format($item->patient_share) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th scope="row">Total</th>
                        <td class="text-end text-nowrap">{{ Fcfa::format($authorization->billed_total) }}</td>
                        <td class="text-end">—</td>
                        <td class="text-end text-nowrap">{{ Fcfa::format($authorization->insurer_total) }}</td>
                        <td class="text-end text-nowrap">{{ Fcfa::format($authorization->patient_total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p>
            Seuls les actes mentionnés ci-dessus sont pris en charge.
            @if ($authorization->overrun_total > 0)
                Le reste à charge comprend un dépassement de
                {{ Fcfa::format($authorization->overrun_total) }} par rapport aux tarifs officiels.
            @endif
        </p>
    </article>
@endsection
