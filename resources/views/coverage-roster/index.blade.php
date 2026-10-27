@extends('layouts.app')

@section('title', 'Portefeuille assuré')

@section('content')
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Portefeuille assuré</h1>
            <p class="text-body-secondary mb-0">Gérez les bénéficiaires et contrats de votre assureur.</p>
        </div>
        @if ($insurer)
            <a class="btn btn-primary" href="{{ route('coverage-roster.create', ['insurer_id' => $insurer->id]) }}">
                Ajouter un bénéficiaire
            </a>
        @endif
    </div>

    @if (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
        <form method="GET" action="{{ route('coverage-roster.index') }}" class="card card-body mb-4">
            <label for="insurer_id" class="form-label">Assureur à administrer</label>
            <div class="d-flex gap-2">
                <select class="form-select" name="insurer_id" id="insurer_id" required>
                    <option value="">Choisir un assureur</option>
                    @foreach ($insurers as $option)
                        <option value="{{ $option->id }}" @selected($insurer?->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-primary" type="submit">Afficher</button>
            </div>
        </form>
    @endif

    @if ($insurer)
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5">Importer un fichier CSV · {{ $insurer->name }}</h2>
                <p>Le fichier est d’abord vérifié. Rien n’est enregistré avant confirmation de l’aperçu.</p>
                <p><a href="{{ route('coverage-roster.template') }}">Télécharger le modèle CSV avec une ligne fictive</a></p>
                <form method="POST" action="{{ route('coverage-roster.import.preview') }}" enctype="multipart/form-data">
                    @csrf
                    @if (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
                        <input type="hidden" name="insurer_id" value="{{ $insurer->id }}">
                    @endif
                    <label class="form-label" for="csv_file">Fichier CSV (2 Mo maximum)</label>
                    <input class="form-control mb-3" type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required>
                    @error('csv_file')<div class="text-danger mb-3">{{ $message }}</div>@enderror
                    <button class="btn btn-outline-primary" type="submit">Vérifier avant import</button>
                </form>
            </div>
        </section>

        <section aria-labelledby="roster-title">
            <h2 id="roster-title" class="h4">Bénéficiaires enregistrés</h2>
            @if ($beneficiaries->isEmpty())
                <p class="alert alert-info">Aucun bénéficiaire dans le portefeuille sélectionné.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Bénéficiaire</th>
                                <th scope="col">N° bénéficiaire</th>
                                <th scope="col">Assuré principal</th>
                                <th scope="col">Souscripteur</th>
                                <th scope="col">Contrats</th>
                                <th scope="col"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($beneficiaries as $beneficiary)
                                <tr>
                                    <td>{{ $beneficiary->name }}</td>
                                    <td>{{ $beneficiary->member_number }}</td>
                                    <td>{{ $beneficiary->policyholder->name }}</td>
                                    <td>{{ $beneficiary->policyholder->subscriber->name }}</td>
                                    <td>
                                        @foreach ($beneficiary->policyholder->subscriber->policies as $policy)
                                            <div>{{ $policy->number }} · {{ $policy->coverage_rate }} %</div>
                                        @endforeach
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('coverage-roster.edit', $beneficiary) }}">Corriger</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $beneficiaries->links() }}
            @endif
        </section>
    @elseif (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
        <p class="alert alert-info">Choisissez un assureur pour consulter ou corriger son portefeuille.</p>
    @endif
@endsection
