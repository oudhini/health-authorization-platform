@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
    <section class="row justify-content-center py-4">
        <div class="col-sm-10 col-md-7 col-lg-5">
            <div class="mb-4 text-center">
                <h1 class="h2 mb-1">Connexion</h1>
                <p class="text-body-secondary mb-0">Accédez à votre espace sécurisé.</p>
            </div>
            <form class="card border-0 shadow-sm" method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label" for="email">Adresse e-mail</label>
                        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                            type="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        @error('email')
                            <div class="invalid-feedback" id="email-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Mot de passe</label>
                        <input class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                            type="password" autocomplete="current-password" required
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        @error('password')
                            <div class="invalid-feedback" id="password-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </div>
            </form>
        </div>
    </section>
@endsection
