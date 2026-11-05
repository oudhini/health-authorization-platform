@extends('layouts.app')

@section('title', 'Catalogue des actes et tarifs')

@section('content')
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="hap-stat-icon bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
                <i class="bi bi-journal-medical"></i>
            </span>
            <h1 class="h2 mb-0 fw-bold">Catalogue commun des actes et tarifs</h1>
        </div>
        <p class="text-body-secondary mb-0">
            Les tarifs sont historisés par date d’effet. Une modification ne change pas les montants déjà copiés sur une demande.
        </p>
    </div>

    <!-- Create Act Form Card -->
    <section class="card border-0 shadow-sm mb-4 overflow-hidden" aria-labelledby="create-act-title">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle text-primary"></i>
            <h2 class="h5 mb-0 fw-bold" id="create-act-title">Ajouter un acte et son tarif initial</h2>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.medical-catalog.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label" for="code">Code</label>
                    <input class="form-control" id="code" name="code" value="{{ old('code') }}" maxlength="50" placeholder="Ex. CONSULT-GEN" required>
                    @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="label">Libellé</label>
                    <input class="form-control" id="label" name="label" value="{{ old('label') }}" maxlength="255" placeholder="Ex. Consultation spécialisée" required>
                    @error('label')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="amount">Tarif (FCFA)</label>
                    <div class="input-group">
                        <input class="form-control" id="amount" name="amount" type="number" min="0" step="1"
                            value="{{ old('amount') }}" placeholder="5000" required>
                        <span class="input-group-text bg-light text-muted small">FCFA</span>
                    </div>
                    @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="effective_from">Date d’effet</label>
                    <input class="form-control" id="effective_from" name="effective_from" type="date"
                        min="{{ now()->toDateString() }}" value="{{ old('effective_from', now()->toDateString()) }}" required>
                    @error('effective_from')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100 shadow-sm" type="submit">
                        <i class="bi bi-plus-lg"></i>
                        <span>Ajouter</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    @if ($acts->isEmpty())
        <div class="alert alert-info shadow-sm d-flex align-items-center gap-2" role="status">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
            <div>Aucun acte médical dans le catalogue.</div>
        </div>
    @else
        <div class="d-flex flex-column gap-3 mb-4">
            @foreach ($acts as $act)
                <section class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-light border text-primary fw-bold font-monospace px-2 py-1">
                                {{ $act->code }}
                            </span>
                            <h2 class="h5 mb-0 fw-bold text-dark">{{ $act->code }} · {{ $act->label }}</h2>
                        </div>
                        <span class="badge rounded-pill {{ $act->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $act->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                    <div class="card-body p-4">
                        <!-- Update Act Metadata Form -->
                        <form method="POST" action="{{ route('admin.medical-catalog.update', $act) }}"
                            class="row g-3 align-items-end mb-4 p-3 bg-light rounded-3 border">
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
                                <div class="form-check pt-3">
                                    <input class="form-check-input" id="active_{{ $act->id }}" name="is_active" type="checkbox"
                                        value="1" @checked($act->is_active)>
                                    <label class="form-check-label fw-semibold" for="active_{{ $act->id }}">Actif</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100" type="submit">
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    Mettre à jour
                                </button>
                            </div>
                        </form>

                        <!-- Tariffs Timeline -->
                        <h3 class="h6 fw-bold text-dark mt-2 mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-primary"></i>
                            <span>Historique des tarifs (FCFA)</span>
                        </h3>
                        @if ($act->tariffs->isEmpty())
                            <p class="text-body-secondary small mb-3">Aucun tarif défini; cet acte ne peut pas être utilisé pour une demande.</p>
                        @else
                            <ul class="list-group list-group-flush mb-3 border rounded-3 overflow-hidden">
                                @foreach ($act->tariffs->sortByDesc('effective_from') as $tariff)
                                    <li class="list-group-item px-3 py-2 d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark tabular-nums">
                                            <i class="bi bi-tag-fill text-muted me-1 small"></i>
                                            {{ number_format($tariff->amount, 0, ',', ' ') }} FCFA
                                        </span>
                                        <span class="badge text-bg-light border text-secondary">
                                            Applicable dès le {{ $tariff->effective_from->format('d/m/Y') }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <!-- Add Future Tariff Form -->
                        <form method="POST" action="{{ route('admin.medical-catalog.tariffs.store', $act) }}"
                            class="row g-2 align-items-end pt-2">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label small" for="amount_{{ $act->id }}">Ajouter un tarif futur (FCFA)</label>
                                <div class="input-group input-group-sm">
                                    <input class="form-control" id="amount_{{ $act->id }}" name="amount" type="number"
                                        min="0" step="1" placeholder="Ex. 6000" required>
                                    <span class="input-group-text bg-light text-muted">FCFA</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small" for="date_{{ $act->id }}">Date d’effet (aujourd’hui ou après)</label>
                                <input class="form-control form-control-sm" id="date_{{ $act->id }}" name="effective_from" type="date"
                                    min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-primary btn-sm w-100" type="submit">
                                    <i class="bi bi-calendar-plus me-1"></i>
                                    Ajouter au tarif
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endforeach
        </div>
        {{ $acts->links('partials.pagination') }}
    @endif
@endsection
