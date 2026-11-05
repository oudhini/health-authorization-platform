<!DOCTYPE html>
<html lang="fr" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f4c81">
    <title>@yield('title', 'Plateforme de prise en charge') · Health Authorization Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex min-vh-100 flex-column bg-light">
    <a class="visually-hidden-focusable position-absolute top-0 start-0 z-3 bg-white p-3 shadow-sm rounded-bottom text-primary fw-bold" href="#contenu">
        Aller au contenu principal
    </a>

    <header class="no-print">
        <nav class="navbar navbar-expand-lg navbar-dark hap-navbar py-2 py-lg-3">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <span class="hap-brand-icon">
                        <i class="bi bi-shield-plus"></i>
                    </span>
                    <span>
                        Health Authorization
                        <span class="d-none d-sm-inline opacity-75 fw-normal fs-6">Platform</span>
                    </span>
                </a>

                <button class="navbar-toggler border-0 shadow-none px-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNavigation" aria-controls="mainNavigation"
                    aria-expanded="false" aria-label="Afficher la navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse mt-3 mt-lg-0" id="mainNavigation">
                    @auth
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                    <i class="bi bi-grid-1x2"></i>
                                    <span>Accueil</span>
                                </a>
                            </li>
                            @if (Route::has('facility.requests.index') && auth()->user()->role === \App\Enums\UserRole::FacilityAgent)
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('facility.requests.index', 'facility.requests.show') ? 'active' : '' }}" href="{{ route('facility.requests.index') }}">
                                        <i class="bi bi-folder2-open"></i>
                                        <span>Mes demandes</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('facility.requests.create') ? 'active' : '' }}" href="{{ route('facility.requests.create') }}">
                                        <i class="bi bi-plus-circle"></i>
                                        <span>Nouvelle demande</span>
                                    </a>
                                </li>
                            @endif
                            @if (Route::has('insurer.requests.index') && auth()->user()->role === \App\Enums\UserRole::InsurerManager)
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('insurer.requests.*') ? 'active' : '' }}" href="{{ route('insurer.requests.index') }}">
                                        <i class="bi bi-inbox"></i>
                                        <span>Demandes reçues</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('coverage-roster.*') ? 'active' : '' }}" href="{{ route('coverage-roster.index') }}">
                                        <i class="bi bi-people"></i>
                                        <span>Portefeuille assuré</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->role === \App\Enums\UserRole::PlatformAdmin)
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                        <i class="bi bi-person-gear"></i>
                                        <span>Comptes</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.organizations.*') ? 'active' : '' }}" href="{{ route('admin.organizations.index') }}">
                                        <i class="bi bi-buildings"></i>
                                        <span>Organisations</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.medical-catalog.*') ? 'active' : '' }}" href="{{ route('admin.medical-catalog.index') }}">
                                        <i class="bi bi-journal-medical"></i>
                                        <span>Actes et tarifs</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                                        <i class="bi bi-shield-check"></i>
                                        <span>Journal d'audit</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.facility-insurers.*') ? 'active' : '' }}" href="{{ route('admin.facility-insurers.index') }}">
                                        <i class="bi bi-diagram-3"></i>
                                        <span>Assureurs partenaires</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('coverage-roster.*') ? 'active' : '' }}" href="{{ route('coverage-roster.index') }}">
                                        <i class="bi bi-people"></i>
                                        <span>Portefeuille assuré</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 pt-3 pt-lg-0 border-top border-white border-opacity-10 border-lg-0">
                            <div class="hap-user-badge text-white">
                                <i class="bi bi-person-circle"></i>
                                <span>{{ auth()->user()->name }} · {{ auth()->user()->role->label() }}</span>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1 w-100">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Se déconnecter</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="ms-auto pt-2 pt-lg-0">
                            <a class="btn btn-outline-light px-3" href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-right me-1"></i>
                                <span>Se connecter</span>
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main class="container flex-grow-1 py-4 py-lg-5" id="contenu">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="no-print border-top bg-white py-4 mt-auto">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 text-body-secondary small">
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-primary">Santé & Assurances</span>
                <span>Health Authorization Platform · Environnement de développement · Données de test</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center gap-1 text-success">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>Transactions sécurisées</span>
                </span>
            </div>
        </div>
    </footer>
</body>
</html>
