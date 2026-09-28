@extends('layouts.guest')
@section('title', 'Entrar')
@section('content')
<div class="auth-shell">
    <section class="auth-hero">
        <div class="auth-hero__glow"></div>
        <div class="auth-hero__grid"></div>
        <header class="auth-hero__brand">
            @if(!empty($brand['logo']))
                <img class="brand-logo brand-logo--auth" src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}">
            @else
                <div class="brand-mark">{{ $brand['mark'] ?? 'EL' }}</div>
            @endif
            <div>
                <strong>{{ strtoupper($brand['name'] ?? 'EXCLUSIVE LOCKER') }}</strong>
                <small>{{ $brand['subtitle'] ?? 'Control Center · Master' }}</small>
            </div>
        </header>
        <div class="auth-hero__copy">
            <p class="eyebrow">Operação em tempo real</p>
            <h1>Inteligência operacional para lockers e correspondências.</h1>
            <p>Monitore condomínios, senhas, ocupação e pendências em um único painel com auditoria completa.</p>
        </div>
        <footer class="auth-hero__foot">
            <span>Criptografia de sessão</span>
            <span>Trilha de auditoria</span>
            <span>API Exclusive Locker</span>
        </footer>
    </section>

    <section class="auth-panel">
        <div class="auth-panel__card">
            <p class="eyebrow">Acesso administrativo</p>
            <h2>Entre na plataforma</h2>
            <p class="lead">Use suas credenciais corporativas. Todas as tentativas são registradas.</p>

            @if(session('ok'))
                <div class="auth-alert auth-alert--ok">{{ session('ok') }}</div>
            @endif
            @if($errors->any())
                <div class="auth-alert auth-alert--err">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('login') }}" class="auth-form" autocomplete="on">
                @csrf
                <label>
                    <span>E-mail corporativo</span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="nome@empresa.com" required autofocus>
                </label>
                <label>
                    <span>Senha</span>
                    <div class="password-field">
                        <input id="password" type="password" name="password" placeholder="••••••••" required minlength="6">
                        <button type="button" class="ghost-btn" data-toggle-password="password">Mostrar</button>
                    </div>
                </label>
                <div class="auth-row">
                    <label class="check">
                        <input type="checkbox" name="remember" value="1">
                        <span>Manter conectado neste dispositivo</span>
                    </label>
                    <a href="{{ route('password.request') }}">Esqueci a senha</a>
                </div>
                <button class="auth-submit" type="submit">Acessar o painel</button>
            </form>

            <ul class="auth-notes">
                <li>Sessão com regeneração de token após o login</li>
                <li>Proteção contra tentativas excessivas</li>
                <li>Ambiente interno · acesso restrito a operadores</li>
            </ul>
        </div>
    </section>
</div>
@endsection
