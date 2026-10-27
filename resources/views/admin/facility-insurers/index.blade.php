@extends('layouts.app')

@section('title', 'Assureurs partenaires')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Assureurs partenaires</h1>
        <p class="text-body-secondary mb-0">
            Configurez les assureurs auxquels chaque établissement peut adresser des demandes.
        </p>
    </div>

    @if ($insurers->isEmpty())
        <div class="alert alert-warning" role="status">Aucun assureur n'est configuré dans la plateforme.</div>
    @elseif ($facilities->isEmpty())
        <div class="alert alert-info" role="status">Aucun établissement n'est configuré dans la plateforme.</div>
    @else
        <div class="row g-3">
            @foreach ($facilities as $facility)
                <div class="col-lg-6">
                    <section class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h5">{{ $facility->name }}</h2>
                            <form method="POST"
                                action="{{ route('admin.facility-insurers.update', $facility) }}">
                                @csrf
                                @method('PUT')
                                <fieldset>
                                    <legend class="form-label fs-6">Assureurs autorisés</legend>
                                    @foreach ($insurers as $insurer)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                id="facility_{{ $facility->id }}_insurer_{{ $insurer->id }}"
                                                name="insurer_ids[]" value="{{ $insurer->id }}"
                                                @checked($facility->partnerInsurers->contains('id', $insurer->id))>
                                            <label class="form-check-label"
                                                for="facility_{{ $facility->id }}_insurer_{{ $insurer->id }}">
                                                {{ $insurer->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </fieldset>
                                <button type="submit" class="btn btn-primary mt-3">
                                    Enregistrer les partenaires
                                </button>
                            </form>
                        </div>
                    </section>
                </div>
            @endforeach
        </div>
    @endif
@endsection
