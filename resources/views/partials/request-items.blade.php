@use('App\Support\Fcfa')

@php($totals = $authorizationRequest->coverageTotals())

<div class="table-responsive rounded-3 border">
    <table class="table table-hover align-middle mb-0">
        <caption class="caption-top px-3 pt-3 pb-2 text-body-secondary small">
            <i class="bi bi-info-circle me-1"></i>
            Montants par acte, taux de prise en charge appliqué : <strong>{{ $authorizationRequest->coverage_rate }} %</strong>.
        </caption>
        <thead class="table-light">
            <tr>
                <th scope="col" class="ps-3">Acte</th>
                <th scope="col" class="text-end text-nowrap">Facturé</th>
                <th scope="col" class="text-end text-nowrap">Tarif officiel</th>
                <th scope="col" class="text-end text-nowrap">Accepté</th>
                <th scope="col" class="text-end text-nowrap text-primary">Part assureur</th>
                <th scope="col" class="text-end text-nowrap text-secondary">Reste à charge</th>
                <th scope="col" class="text-end text-nowrap pe-3">Dépassement</th>
            </tr>
        </thead>
        <tbody class="border-top-0">
            @foreach ($authorizationRequest->items as $item)
                <tr>
                    <td class="ps-3 fw-medium">
                        <span class="d-inline-flex align-items-center gap-2">
                            <span class="badge rounded-circle p-1 text-bg-light border text-primary">
                                <i class="bi bi-activity"></i>
                            </span>
                            {{ $item->medicalAct->label }}
                        </span>
                    </td>
                    <td class="text-end text-nowrap tabular-nums">{{ Fcfa::format($item->billed_amount) }}</td>
                    <td class="text-end text-nowrap tabular-nums text-muted">{{ Fcfa::format($item->official_tariff) }}</td>
                    <td class="text-end text-nowrap tabular-nums">{{ Fcfa::format($item->accepted_amount) }}</td>
                    <td class="text-end text-nowrap tabular-nums fw-semibold text-primary">{{ Fcfa::format($item->insurer_share) }}</td>
                    <td class="text-end text-nowrap tabular-nums fw-semibold">{{ Fcfa::format($item->patient_share) }}</td>
                    <td class="text-end text-nowrap tabular-nums pe-3 text-muted">{{ Fcfa::format($item->overrun) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="table-light fw-bold border-top">
            <tr>
                <td class="ps-3">Total</td>
                <td class="text-end text-nowrap tabular-nums">{{ Fcfa::format($totals->billedAmount) }}</td>
                <td class="text-end text-muted">—</td>
                <td class="text-end text-nowrap tabular-nums">{{ Fcfa::format($totals->acceptedAmount) }}</td>
                <td class="text-end text-nowrap tabular-nums text-primary">{{ Fcfa::format($totals->insurerShare) }}</td>
                <td class="text-end text-nowrap tabular-nums text-dark">{{ Fcfa::format($totals->patientShare) }}</td>
                <td class="text-end text-nowrap tabular-nums pe-3 text-muted">{{ Fcfa::format($totals->overrun) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<p class="small text-body-secondary mt-2 mb-0 d-flex align-items-center gap-1">
    <i class="bi bi-shield-exclamation text-warning"></i>
    <span>Le dépassement est déjà inclus dans le reste à charge.</span>
</p>
