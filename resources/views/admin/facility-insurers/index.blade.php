@extends('layouts.app')

@section('title', 'Assureurs partenaires')

@section('content')
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                <i class="bi bi-diagram-3-fill"></i>
            </span>
            <h1 class="h2 mb-0 fw-bold">Assureurs partenaires</h1>
        </div>
        <p class="text-body-secondary mb-0">
            Configurez les assureurs auxquels chaque établissement peut adresser des demandes.
        </p>
    </div>

    @if ($insurers->isEmpty())
        <div class="alert alert-warning shadow-sm d-flex align-items-center gap-2" role="status">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
            <div>Aucun assureur n'est configuré dans la plateforme.</div>
        </div>
    @elseif ($facilities->isEmpty())
        <div class="alert alert-info shadow-sm d-flex align-items-center gap-2" role="status">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
            <div>Aucun établissement n'est configuré dans la plateforme.</div>
        </div>
    @else
        <div class="row g-4">
            @foreach ($facilities as $facility)
                <div class="col-lg-6">
                    <section class="card border-0 shadow-sm h-100 overflow-hidden">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 32px; height: 32px; font-size: 1rem;">
                                    <i class="bi bi-hospital"></i>
                                </span>
                                <h2 class="h5 mb-0 fw-bold text-dark">{{ $facility->name }}</h2>
                            </div>
                            <span class="badge rounded-pill text-bg-light border text-muted">
                                {{ $facility->partnerInsurers->count() }} conventionné(s)
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('admin.facility-insurers.update', $facility) }}">
                                @csrf
                                @method('PUT')
                                <fieldset class="mb-3">
                                    <legend class="form-label fs-6 fw-bold text-dark mb-3">Assureurs autorisés</legend>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($insurers as $insurer)
                                            <div class="form-check p-3 bg-light rounded-3 border d-flex align-items-center gap-2">
                                                <input class="form-check-input mt-0" type="checkbox"
                                                    id="facility_{{ $facility->id }}_insurer_{{ $insurer->id }}"
                                                    name="insurer_ids[]" value="{{ $insurer->id }}"
                                                    @checked($facility->partnerInsurers->contains('id', $insurer->id))>
                                                <label class="form-check-label flex-grow-1 fw-semibold text-dark"
                                                    for="facility_{{ $facility->id }}_insurer_{{ $insurer->id }}">
                                                    <i class="bi bi-shield-check text-muted me-1"></i>
                                                    {{ $insurer->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </fieldset>
                                <button type="submit" class="btn btn-primary shadow-sm px-4">
                                    <i class="bi bi-check-lg me-1"></i>
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
