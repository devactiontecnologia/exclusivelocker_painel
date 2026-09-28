@extends('layouts.guest')
@section('title', 'Recuperar senha')
@section('content')
<div class="auth-shell">
    <section class="auth-hero">
        <div class="auth-hero__glow"></div>
        <div class="auth-hero__grid"></div>
        <header class="auth-hero__brand">
            <div class="brand-mark">EL</div>
            <div>
                <strong>EXCLUSIVE LOCKER</strong>
                <small>Recuperação segura</small>
            </div>
        </header>
        <div class="auth-hero__copy">
            <p class="eyebrow">Identidade protegida</p>
            <h1>Redefina o acesso sem expor o cadastro.</h1>
            <p>Enviamos o link somente se o e-mail existir na base. A solicitação entra na auditoria.</p>
        </div>
    </section>
    <section class="auth-panel">
        <div class="auth-panel__card">
            <p class="eyebrow">Recuperar senha</p>
            <h2>Informe seu e-mail</h2>
            <p class="lead">Você receberá as instruções se o endereço estiver autorizado.</p>
            @if(session('ok'))
                <div class="auth-alert auth-alert--ok">{{ session('ok') }}</div>
            @endif
            @if($errors->any())
                <div class="auth-alert auth-alert--err">{{ $errors->first() }}</div>
            @endif
            <form method="post" action="{{ route('password.email') }}" class="auth-form">
                @csrf
                <label>
                    <span>E-mail corporativo</span>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </label>
                <button class="auth-submit" type="submit">Enviar instruções</button>
                <a class="auth-back" href="{{ route('login') }}">Voltar ao login</a>
            </form>
        </div>
    </section>
</div>
@endsection
