<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de prise en charge {{ $authorization->number }}</title>
    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }

        h1 {
            color: #153f72;
            font-size: 20px;
            margin: 0;
        }

        h2 {
            color: #153f72;
            font-size: 13px;
            margin: 20px 0 8px;
        }

        .muted {
            color: #596579;
        }

        .banner {
            border: 1px solid #d5deea;
            margin: 16px 0;
            padding: 12px;
        }

        .columns {
            width: 100%;
        }

        .columns td {
            vertical-align: top;
            width: 33.33%;
        }

        dl {
            margin: 0;
        }

        dt {
            color: #596579;
            font-size: 8px;
            margin-top: 6px;
        }

        dd {
            font-weight: bold;
            margin: 0;
        }

        table.items {
            border-collapse: collapse;
            width: 100%;
        }

        .items th,
        .items td {
            border: 1px solid #cbd4df;
            padding: 6px;
        }

        .items th {
            background: #edf2f8;
            text-align: left;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .notice {
            border-top: 1px solid #cbd4df;
            margin-top: 24px;
            padding-top: 10px;
        }

        .footer {
            color: #596579;
            font-size: 8px;
            margin-top: 18px;
            text-align: center;
        }
    </style>
</head>
<body>
    @use('App\Support\Fcfa')

    <h1>Bon de prise en charge</h1>
    <div class="muted">Référence : {{ $authorization->number }}</div>
    <div class="muted">Document de démonstration — sans valeur contractuelle</div>

    <div class="banner">
        <strong>Valable du {{ $authorization->valid_from->format('d/m/Y') }}
            au {{ $authorization->valid_until->format('d/m/Y') }}</strong>
        <br>
        Seuls les actes détaillés dans ce bon sont concernés.
    </div>

    <table class="columns">
        <tr>
            <td>
                <dl>
                    <dt>Assureur</dt>
                    <dd>{{ $authorizationRequest->insurer->name }}</dd>
                    <dt>Établissement</dt>
                    <dd>{{ $authorizationRequest->facility->name }}</dd>
                    <dt>Police</dt>
                    <dd>{{ $authorizationRequest->policy->number }}</dd>
                </dl>
            </td>
            <td>
                <dl>
                    <dt>Bénéficiaire</dt>
                    <dd>{{ $authorizationRequest->beneficiary->name }}</dd>
                    <dt>Numéro bénéficiaire</dt>
                    <dd>{{ $authorizationRequest->beneficiary->member_number }}</dd>
                    <dt>Assuré principal</dt>
                    <dd>{{ $authorizationRequest->beneficiary->policyholder->name }}</dd>
                </dl>
            </td>
            <td>
                <dl>
                    <dt>Taux de prise en charge</dt>
                    <dd>{{ $authorizationRequest->coverage_rate }} %</dd>
                    <dt>Décision</dt>
                    <dd>{{ $authorization->created_at->format('d/m/Y') }}</dd>
                </dl>
            </td>
        </tr>
    </table>

    <h2>Actes pris en charge</h2>
    <table class="items">
        <thead>
            <tr>
                <th>Acte</th>
                <th class="number">Facturé</th>
                <th class="number">Tarif de référence</th>
                <th class="number">Part assureur</th>
                <th class="number">Part bénéficiaire</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($authorizationRequest->items as $item)
                <tr>
                    <td>{{ $item->medicalAct->label }}</td>
                    <td class="number">{{ Fcfa::format($item->billed_amount) }}</td>
                    <td class="number">{{ Fcfa::format($item->official_tariff) }}</td>
                    <td class="number">{{ Fcfa::format($item->insurer_share) }}</td>
                    <td class="number">{{ Fcfa::format($item->patient_share) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th class="number">{{ Fcfa::format($authorization->billed_total) }}</th>
                <th class="number">—</th>
                <th class="number">{{ Fcfa::format($authorization->insurer_total) }}</th>
                <th class="number">{{ Fcfa::format($authorization->patient_total) }}</th>
            </tr>
        </tfoot>
    </table>

    @if ($authorization->overrun_total > 0)
        <p class="notice">
            Le reste à charge comprend un dépassement de
            {{ Fcfa::format($authorization->overrun_total) }} par rapport aux tarifs de référence.
        </p>
    @endif

    <div class="footer">
        Généré le {{ now()->format('d/m/Y H:i') }} ·  Health Authorization Platform ·
        Vérifier les règles applicables auprès de l’assureur.
    </div>
</body>
</html>
