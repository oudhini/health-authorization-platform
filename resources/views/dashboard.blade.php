@extends('layouts.app')

@section('title', 'Accueil')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1 fw-bold text-dark">Bonjour {{ $user->name }}</h1>
            <p class="text-body-secondary mb-0">Votre espace de travail sécurisé.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill text-bg-primary px-3 py-2">
                <i class="bi bi-shield-check me-1"></i>
                Session sécurisée
            </span>
        </div>
    </div>

    <!-- User & Organization Summary Card -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="hap-stat-icon bg-primary bg-opacity-10 text-primary">
                        @if ($user->role === \App\Enums\UserRole::FacilityAgent)
                            <i class="bi bi-hospital"></i>
                        @elseif ($user->role === \App\Enums\UserRole::InsurerManager)
                            <i class="bi bi-shield-check"></i>
                        @else
                            <i class="bi bi-gear-wide-connected"></i>
                        @endif
                    </div>
                    <div>
                        <div class="text-muted small">Rôle & Rattachement</div>
                        <p class="mb-0 fs-5">
                            Vous êtes connecté en tant que <strong>{{ $user->role->label() }}</strong>
                            @if ($user->organization)
                                pour <strong>{{ $user->organization->name }}</strong>
                            @endif.
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if (Route::has('facility.requests.index') && $user->role === \App\Enums\UserRole::FacilityAgent)
                        <a class="btn btn-primary shadow-sm" href="{{ route('facility.requests.create') }}">
                            <i class="bi bi-plus-circle me-1"></i>
                            <span>Nouvelle demande</span>
                        </a>
                        <a class="btn btn-outline-primary" href="{{ route('facility.requests.index') }}">
                            <i class="bi bi-folder2-open me-1"></i>
                            <span>Mes demandes</span>
                        </a>
                    @endif

                    @if (Route::has('insurer.requests.index') && $user->role === \App\Enums\UserRole::InsurerManager)
                        <a class="btn btn-primary shadow-sm" href="{{ route('insurer.requests.index') }}">
                            <i class="bi bi-inbox me-1"></i>
                            <span>Demandes reçues</span>
                        </a>
                        <a class="btn btn-outline-primary" href="{{ route('coverage-roster.index') }}">
                            <i class="bi bi-people me-1"></i>
                            <span>Portefeuille assuré</span>
                        </a>
                    @endif

                    @if ($user->role === \App\Enums\UserRole::PlatformAdmin)
                        <a class="btn btn-primary shadow-sm" href="{{ route('admin.audit-logs.index') }}">
                            <i class="bi bi-shield-check me-1"></i>
                            <span>Journal d'audit</span>
                        </a>
                        <a class="btn btn-outline-primary" href="{{ route('admin.facility-insurers.index') }}">
                            <i class="bi bi-diagram-3 me-1"></i>
                            <span>Assureurs partenaires</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Contextual Shortcuts -->
    <div class="row g-4">
        @if ($user->role === \App\Enums\UserRole::FacilityAgent)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 card-hover border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="hap-stat-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-file-earmark-plus"></i>
                            </div>
                            <h2 class="h5 mb-0">Émettre une prise en charge</h2>
                        </div>
                        <p class="text-body-secondary small flex-grow-1">
                            Recherchez le bénéficiaire par nom ou numéro, vérifiez la police active et sélectionnez les actes prescrits.
                        </p>
                        <a class="btn btn-outline-primary w-100 mt-2" href="{{ route('facility.requests.create') }}">
                            Créer une demande &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 card-hover border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="hap-stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h2 class="h5 mb-0">Suivi des dossiers</h2>
                        </div>
                        <p class="text-body-secondary small flex-grow-1">
                            Consultez l'historique de votre établissement, reprenez vos brouillons ou complétez les demandes d'informations.
                        </p>
                        <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('facility.requests.index') }}">
                            Voir les demandes &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 card-hover border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="hap-stat-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-printer"></i>
                            </div>
                            <h2 class="h5 mb-0">Bons de prise en charge</h2>
                        </div>
                        <p class="text-body-secondary small flex-grow-1">
                            Dès approbation par l'assureur, accédez directement au bon officiel prêt pour impression et facturation.
                        </p>
                        <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('facility.requests.index') }}">
                            Accéder aux bons &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @elseif ($user->role === \App\Enums\UserRole::InsurerManager)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 card-hover border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="hap-stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-inbox-fill"></i>
                            </div>
                            <h2 class="h5 mb-0">Demandes en attente</h2>
                        </div>
                        <p class="text-body-secondary small flex-grow-1">
                            Instruisez les demandes transmises par les établissements conventionnés, vérifiez les pièces et validez les prises en charge.
                        </p>
                        <a class="btn btn-outline-primary w-100 mt-2" href="{{ route('insurer.requests.index') }}">
                            Ouvrir la file d'attente &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 card-hover border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="hap-stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <h2 class="h5 mb-0">Portefeuille assuré</h2>
                        </div>
                        <p class="text-body-secondary small flex-grow-1">
                            Gérez les souscripteurs, assurés principaux et bénéficiaires. Importez les données de contrats par fichier CSV.
                        </p>
                        <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('coverage-roster.index') }}">
                            Gérer le portefeuille &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 card-hover border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="hap-stat-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h2 class="h5 mb-0">Calculs & Tarifs</h2>
                        </div>
                        <p class="text-body-secondary small flex-grow-1">
                            Le moteur applique automatiquement les plafonds officiels et les quotes-parts pour garantir la conformité contractuelle.
                        </p>
                        <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('insurer.requests.index') }}">
                            Consulter &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @elseif ($user->role === \App\Enums\UserRole::PlatformAdmin)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 card-hover border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="hap-stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <h2 class="h5 mb-0">Journal d'audit</h2>
                        </div>
                        <p class="text-body-secondary small flex-grow-1">
                            Traçabilité complète des envois, approbations, rejets, modifications de comptes et téléchargements de documents.
                        </p>
                        <a class="btn btn-outline-primary w-100 mt-2" href="{{ route('admin.audit-logs.index') }}">
                            Inspecter l'audit &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 card-hover border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="hap-stat-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-diagram-3-fill"></i>
                            </div>
                            <h2 class="h5 mb-0">Partenariats Établissements</h2>
                        </div>
                        <p class="text-body-secondary small flex-grow-1">
                            Configurez les conventions autorisées entre chaque établissement de santé et les assureurs partenaires.
                        </p>
                        <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('admin.facility-insurers.index') }}">
                            Configurer &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 card-hover border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="hap-stat-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-journal-medical"></i>
                            </div>
                            <h2 class="h5 mb-0">Catalogue & Tarifs</h2>
                        </div>
                        <p class="text-body-secondary small flex-grow-1">
                            Gérez le référentiel des actes médicaux et l'historique daté des tarifs conventionnels officiels.
                        </p>
                        <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('admin.medical-catalog.index') }}">
                            Gérer le catalogue &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
