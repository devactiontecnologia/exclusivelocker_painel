@extends('layouts.guest')
@section('title', 'Nova senha')
@section('content')
<div class="auth-shell">
    <section class="auth-hero">
        <div class="auth-hero__glow"></div>
        <header class="auth-hero__brand">
            <div class="brand-mark">EL</div>
            <div>
                <strong>EXCLUSIVE LOCKER</strong>
                <small>Nova senha</small>
            </div>
        </header>
        <div class="auth-hero__copy">
            <h1>Defina uma senha forte para o Master.</h1>
            <p>Mínimo de 8 caracteres. A sessão anterior será invalidada.</p>
        </div>
    </section>
    <section class="auth-panel">
        <div class="auth-panel__card">
            <h2>Redefinir senha</h2>
            @if($errors->any())
                <div class="auth-alert auth-alert--err">{{ $errors->first() }}</div>
            @endif
            <form method="post" action="{{ route('password.update') }}" class="auth-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <label>
                    <span>E-mail</span>
                    <input type="email" name="email" value="{{ old('email', $email) }}" required>
                </label>
                <label>
                    <span>Nova senha</span>
                    <input type="password" name="password" required minlength="8">
                </label>
                <label>
                    <span>Confirmar senha</span>
                    <input type="password" name="password_confirmation" required minlength="8">
                </label>
                <button class="auth-submit" type="submit">Salvar nova senha</button>
            </form>
        </div>
    </section>
</div>
@endsection
