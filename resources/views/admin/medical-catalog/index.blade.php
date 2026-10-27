@extends('layouts.app')

@section('title', 'Catalogue des actes et tarifs')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Catalogue commun des actes et tarifs</h1>
        <p class="text-body-secondary mb-0">
            Les tarifs sont historisés par date d’effet. Une modification ne change pas les montants déjà copiés sur une demande.
        </p>
    </div>

    <section class="card shadow-sm mb-4" aria-labelledby="create-act-title">
        <div class="card-body">
            <h2 class="h5" id="create-act-title">Ajouter un acte et son tarif initial</h2>
            <form method="POST" action="{{ route('admin.medical-catalog.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label" for="code">Code</label>
                    <input class="form-control" id="code" name="code" value="{{ old('code') }}" maxlength="50" required>
                    @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="label">Libellé</label>
                    <input class="form-control" id="label" name="label" value="{{ old('label') }}" maxlength="255" required>
                    @error('label')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="amount">Tarif (FCFA)</label>
                    <input class="form-control" id="amount" name="amount" type="number" min="0" step="1"
                        value="{{ old('amount') }}" required>
                    @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="effective_from">Date d’effet</label>
                    <input class="form-control" id="effective_from" name="effective_from" type="date"
                        min="{{ now()->toDateString() }}" value="{{ old('effective_from', now()->toDateString()) }}" required>
                    @error('effective_from')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">Ajouter</button></div>
            </form>
        </div>
    </section>

    @if ($acts->isEmpty())
        <div class="alert alert-info" role="status">Aucun acte médical dans le catalogue.</div>
    @else
        @foreach ($acts as $act)
            <section class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <h2 class="h5 mb-1">{{ $act->code }} · {{ $act->label }}</h2>
                            <span class="badge {{ $act->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $act->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.medical-catalog.update', $act) }}"
                        class="row g-2 align-items-end mt-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="is_active" value="0">
                        <div class="col-md-3">
                            <label class="form-label" for="code_{{ $act->id }}">Code</label>
                            <input class="form-control" id="code_{{ $act->id }}" name="code" value="{{ $act->code }}" maxlength="50" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="label_{{ $act->id }}">Libellé</label>
                            <input class="form-control" id="label_{{ $act->id }}" name="label" value="{{ $act->label }}" maxlength="255" required>
                        </div>
                        <div class="col-md-2">
                            <div class="form-check">
                                <input class="form-check-input" id="active_{{ $act->id }}" name="is_active" type="checkbox"
                                    value="1" @checked($act->is_active)>
                                <label class="form-check-label" for="active_{{ $act->id }}">Actif</label>
                            </div>
                        </div>
                        <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Mettre à jour</button></div>
                    </form>

                    <h3 class="h6 mt-4">Historique des tarifs (FCFA)</h3>
                    @if ($act->tariffs->isEmpty())
                        <p class="text-body-secondary">Aucun tarif défini; cet acte ne peut pas être utilisé pour une demande.</p>
                    @else
                        <ul class="list-group list-group-flush mb-3">
                            @foreach ($act->tariffs->sortByDesc('effective_from') as $tariff)
                                <li class="list-group-item px-0 d-flex justify-content-between">
                                    <span>{{ number_format($tariff->amount, 0, ',', ' ') }} FCFA</span>
                                    <span>Applicable dès le {{ $tariff->effective_from->format('d/m/Y') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <form method="POST" action="{{ route('admin.medical-catalog.tariffs.store', $act) }}"
                        class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="amount_{{ $act->id }}">Ajouter un tarif futur (FCFA)</label>
                            <input class="form-control" id="amount_{{ $act->id }}" name="amount" type="number"
                                min="0" step="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="date_{{ $act->id }}">Date d’effet (aujourd’hui ou après)</label>
                            <input class="form-control" id="date_{{ $act->id }}" name="effective_from" type="date"
                                min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-4"><button class="btn btn-outline-primary" type="submit">Ajouter au tarif</button></div>
                    </form>
                </div>
            </section>
        @endforeach
        {{ $acts->links('partials.pagination') }}
    @endif
@endsection
