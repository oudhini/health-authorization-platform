@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
    <section class="row justify-content-center py-4 py-lg-5">
        <div class="col-sm-11 col-md-8 col-lg-5">
            <div class="mb-4 text-center">
                <div class="hap-brand-icon mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.5rem;">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h1 class="h2 mb-1 fw-bold text-dark">Connexion</h1>
                <p class="text-body-secondary mb-0">Accédez à votre espace sécurisé.</p>
            </div>

            <div class="card border-0 shadow-md overflow-hidden">
                <div class="card-header bg-light border-bottom py-3 px-4">
                    <span class="small text-muted fw-semibold d-flex align-items-center gap-1">
                        <i class="bi bi-lock-fill text-success"></i>
                        <span>Portail d'authentification professionnel</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label" for="email">Adresse e-mail</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input class="form-control border-start-0 @error('email') is-invalid @enderror" id="email" name="email"
                                    type="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                                    placeholder="agent@hopital.test"
                                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block mt-1" id="email-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0">
                                    <i class="bi bi-key"></i>
                                </span>
                                <input class="form-control border-start-0 @error('password') is-invalid @enderror" id="password" name="password"
                                    type="password" autocomplete="current-password" required
                                    placeholder="••••••••••••"
                                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block mt-1" id="password-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i>
                            <span>Se connecter</span>
                        </button>
                    </div>
                </form>

                <div class="card-footer bg-light p-3 border-top">
                    <div class="small fw-semibold text-secondary mb-2 d-flex align-items-center gap-1">
                        <i class="bi bi-info-circle"></i>
                        <span>Comptes de test (mot de passe : <code>password</code>)</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2"
                            onclick="document.getElementById('email').value='agent.a@demo.test';document.getElementById('password').value='password';">
                            <i class="bi bi-hospital me-1"></i>Agent Hôpital
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2"
                            onclick="document.getElementById('email').value='manager.alpha@demo.test';document.getElementById('password').value='password';">
                            <i class="bi bi-shield-check me-1"></i>Assureur Alpha
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2"
                            onclick="document.getElementById('email').value='admin@demo.test';document.getElementById('password').value='password';">
                            <i class="bi bi-gear me-1"></i>Admin
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
