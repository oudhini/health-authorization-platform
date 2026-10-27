@use('App\Support\Fcfa')

@php($totals = $authorizationRequest->coverageTotals())

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <caption class="caption-top text-body-secondary">
            Montants par acte, taux de prise en charge appliqué : {{ $authorizationRequest->coverage_rate }} %.
        </caption>
        <thead>
            <tr>
                <th scope="col">Acte</th>
                <th scope="col" class="text-end text-nowrap">Facturé</th>
                <th scope="col" class="text-end text-nowrap">Tarif officiel</th>
                <th scope="col" class="text-end text-nowrap">Accepté</th>
                <th scope="col" class="text-end text-nowrap">Part assureur</th>
                <th scope="col" class="text-end text-nowrap">Reste à charge</th>
                <th scope="col" class="text-end text-nowrap">Dépassement</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($authorizationRequest->items as $item)
                <tr>
                    <td>{{ $item->medicalAct->label }}</td>
                    <td class="text-end text-nowrap">{{ Fcfa::format($item->billed_amount) }}</td>
                    <td class="text-end text-nowrap">{{ Fcfa::format($item->official_tariff) }}</td>
                    <td class="text-end text-nowrap">{{ Fcfa::format($item->accepted_amount) }}</td>
                    <td class="text-end text-nowrap">{{ Fcfa::format($item->insurer_share) }}</td>
                    <td class="text-end text-nowrap">{{ Fcfa::format($item->patient_share) }}</td>
                    <td class="text-end text-nowrap">{{ Fcfa::format($item->overrun) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="text-end text-nowrap">{{ Fcfa::format($totals->billedAmount) }}</td>
                <td class="text-end">—</td>
                <td class="text-end text-nowrap">{{ Fcfa::format($totals->acceptedAmount) }}</td>
                <td class="text-end text-nowrap">{{ Fcfa::format($totals->insurerShare) }}</td>
                <td class="text-end text-nowrap">{{ Fcfa::format($totals->patientShare) }}</td>
                <td class="text-end text-nowrap">{{ Fcfa::format($totals->overrun) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<p class="small text-body-secondary mb-0">Le dépassement est déjà inclus dans le reste à charge.</p>
