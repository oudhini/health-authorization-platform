@extends('layouts.app')

@section('title', 'Accueil')

@section('content')
    <section class="row justify-content-center py-4 py-lg-5">
        <div class="col-lg-9 col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <p class="text-uppercase text-primary fw-semibold small mb-2">Gestion des demandes de prise en charge</p>
                    <h1 class="display-6 fw-bold mb-3">La prise en charge santé, plus simple et mieux suivie</h1>
                    <p class="lead text-body-secondary">
                        Les établissements transmettent aux assureurs partenaires des demandes structurées,
                        suivent leur traitement et consultent les garanties accordées dans un espace sécurisé.
                    </p>
                    <p class="text-body-secondary">
                        Cet environnement utilise des données de test et n'est pas connecté aux systèmes des assureurs.
                        N'y saisissez aucune donnée médicale réelle.
                    </p>
                    <a class="btn btn-primary btn-lg mt-2" href="{{ route('login') }}">Se connecter</a>
                </div>
            </div>
        </div>
    </section>
@endsection
