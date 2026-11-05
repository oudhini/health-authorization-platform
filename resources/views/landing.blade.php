@extends('layouts.app')

@section('title', 'Accueil')

@section('content')
    <!-- Hero Section -->
    <section class="py-4 py-lg-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="hap-hero-badge mb-3">
                    <i class="bi bi-shield-check"></i>
                    <span>Gestion des demandes de prise en charge</span>
                </div>
                <h1 class="display-5 fw-extrabold text-dark mb-3 lh-sm">
                    La prise en charge santé, <br class="d-none d-sm-inline">
                    <span class="text-primary">plus simple et mieux suivie</span>
                </h1>
                <p class="lead text-body-secondary mb-4">
                    Les établissements transmettent aux assureurs partenaires des demandes structurées,
                    suivent leur traitement et consultent les garanties accordées dans un espace sécurisé.
                </p>
                <div class="alert alert-info border-0 shadow-xs p-3 mb-4 d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle-fill fs-5 text-primary flex-shrink-0 mt-1"></i>
                    <div class="small">
                        Cet environnement utilise des données de test et n'est pas connecté aux systèmes des assureurs.
                        N'y saisissez aucune donnée médicale réelle.
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-primary btn-lg px-4 shadow-sm" href="{{ route('login') }}">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        <span>Se connecter</span>
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <!-- Visual Card Showcase -->
                <div class="card border-0 shadow-lg p-3 position-relative overflow-hidden">
                    <div class="position-absolute top-0 end-0 p-3 opacity-10">
                        <i class="bi bi-hospital fs-1 text-primary"></i>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge rounded-pill text-bg-success">
                                <i class="bi bi-check-circle-fill me-1"></i> Système Opérationnel
                            </span>
                            <span class="text-muted small">Portail Tiers-Payant</span>
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-dark">Demande #1042</span>
                                <span class="badge text-bg-primary rounded-pill">Approuvée</span>
                            </div>
                            <div class="small text-muted mb-1">
                                <i class="bi bi-person me-1"></i> Patient : Assuré Démo ALPHA
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="bi bi-hospital me-1"></i> Hôpital Démo A → Assurance Alpha
                            </div>
                            <div class="d-flex justify-content-between pt-2 border-top small">
                                <span class="text-secondary">Part assureur (80%)</span>
                                <span class="fw-bold text-primary">24 000 FCFA</span>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fw-semibold small mb-2 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-key-fill text-primary"></i>
                                <span>Comptes de démonstration</span>
                            </div>
                            <ul class="list-unstyled mb-0 small text-body-secondary d-flex flex-column gap-1">
                                <li><strong>Hôpital :</strong> <code>agent.a@demo.test</code></li>
                                <li><strong>Assureur :</strong> <code>manager.alpha@demo.test</code></li>
                                <li><strong>Admin :</strong> <code>admin@demo.test</code></li>
                                <li class="text-muted pt-1 border-top mt-1">Mot de passe commun : <code>password</code></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3 Feature Pillars -->
        <div class="row g-4 mt-4 pt-3">
            <div class="col-md-4">
                <div class="hap-feature-card">
                    <div class="hap-feature-icon">
                        <i class="bi bi-hospital"></i>
                    </div>
                    <h2 class="h5 mb-2">Établissements de Santé</h2>
                    <p class="text-body-secondary small mb-0">
                        Vérifiez en quelques secondes l'éligibilité d'un assuré, sélectionnez les actes du catalogue officiel et téléversez les justificatifs exigés.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="hap-feature-card">
                    <div class="hap-feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h2 class="h5 mb-2">Gestionnaires d'Assurance</h2>
                    <p class="text-body-secondary small mb-0">
                        Cockpit d'instruction rapide : examinez les demandes, demandez des précisions ou validez instantanément avec calcul automatique des quotes-parts.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="hap-feature-card">
                    <div class="hap-feature-icon">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>
                    <h2 class="h5 mb-2">Bons Infalsifiables</h2>
                    <p class="text-body-secondary small mb-0">
                        Délivrance d'un bon de prise en charge aux montants figés, avec dates de validité contractuelles et génération de PDF téléchargeable à la demande.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
