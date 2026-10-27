@extends('layouts.app')

@section('title', 'Vérification de l’import CSV')

@section('content')
    <h1 class="h2">Vérification de l’import CSV</h1>
    <p class="text-body-secondary">Assureur : {{ $insurer->name }}. Aucun changement n’a encore été enregistré.</p>

    @if ($preview['errors'] !== [])
        <div class="alert alert-danger" role="alert">
            <h2 class="h5">Corrigez le fichier avant de continuer</h2>
            <ul class="mb-0">
                @foreach ($preview['errors'] as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="alert alert-info" role="status">
            {{ $preview['creates'] }} nouveau(x) bénéficiaire(s), {{ $preview['updates'] }} dossier(s) existant(s) à mettre à jour.
        </div>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-striped">
                <thead><tr><th>Ligne</th><th>Bénéficiaire</th><th>N°</th><th>Contrat</th><th>Taux</th></tr></thead>
                <tbody>
                    @foreach ($preview['rows'] as $index => $row)
                        <tr>
                            <td>{{ $index + 2 }}</td>
                            <td>{{ $row['beneficiary_name'] }}</td>
                            <td>{{ $row['member_number'] }}</td>
                            <td>{{ $row['policy_number'] }}</td>
                            <td>{{ $row['coverage_rate'] }} %</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ route('coverage-roster.import') }}" enctype="multipart/form-data" class="card card-body">
            @csrf
            @if (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
                <input type="hidden" name="insurer_id" value="{{ $insurer->id }}">
            @endif
            <input type="hidden" name="fingerprint" value="{{ $fingerprint }}">
            <p>Pour confirmer, sélectionnez à nouveau le même fichier. Il sera revérifié avant l’enregistrement.</p>
            <label class="form-label" for="csv_file">Fichier CSV revérifié</label>
            <input class="form-control mb-3" type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required>
            @error('csv_file')<div class="text-danger mb-3">{{ $message }}</div>@enderror
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Confirmer l’import</button>
                <a class="btn btn-outline-secondary" href="{{ route('coverage-roster.index', ['insurer_id' => $insurer->id]) }}">Annuler</a>
            </div>
        </form>
    @endif
@endsection
