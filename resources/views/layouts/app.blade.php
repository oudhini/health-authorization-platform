<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Plateforme de prise en charge') · Health Authorization Platform</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex min-vh-100 flex-column">
    <a class="visually-hidden-focusable position-absolute top-0 start-0 z-3 bg-white p-3" href="#contenu">
        Aller au contenu
    </a>

    <header class="no-print">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}">Health Authorization Platform</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNavigation" aria-controls="mainNavigation"
                    aria-expanded="false" aria-label="Afficher la navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNavigation">
                    @auth
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('dashboard') }}">Accueil</a>
                            </li>
                            @if (Route::has('facility.requests.index') && auth()->user()->role === \App\Enums\UserRole::FacilityAgent)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('facility.requests.index') }}">Mes demandes</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('facility.requests.create') }}">Nouvelle demande</a>
                                </li>
                            @endif
                            @if (Route::has('insurer.requests.index') && auth()->user()->role === \App\Enums\UserRole::InsurerManager)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('insurer.requests.index') }}">Demandes reçues</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('coverage-roster.index') }}">Portefeuille assuré</a>
                                </li>
                            @endif
                            @if (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.audit-logs.index') }}">Journal d'audit</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.facility-insurers.index') }}">Assureurs partenaires</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('coverage-roster.index') }}">Portefeuille assuré</a>
                                </li>
                            @endif
                        </ul>
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 text-white">
                            <span>{{ auth()->user()->name }} · {{ auth()->user()->role->label() }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-light">Se déconnecter</button>
                            </form>
                        </div>
                    @else
                        <div class="ms-auto">
                            <a class="btn btn-outline-light" href="{{ route('login') }}">Se connecter</a>
                        </div>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main class="container flex-grow-1 py-4" id="contenu">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="no-print border-top bg-white py-3 text-center text-body-secondary">
        Health Authorization Platform · Environnement de développement · Données de test
    </footer>
</body>
</html>
