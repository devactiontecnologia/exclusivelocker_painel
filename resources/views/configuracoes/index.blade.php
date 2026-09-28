@extends('layouts.app')
@section('title','Configurações')
@section('content')
@php
    $tabs = [
        'aparencia' => ['label' => 'Aparência', 'hint' => 'Logo, nome e favicon'],
        'push' => ['label' => 'Notificações push', 'hint' => 'Alertas no app'],
        'email' => ['label' => 'E-mail', 'hint' => 'Disparos SMTP'],
        'manutencao' => ['label' => 'Manutenção', 'hint' => 'Sync e migrations'],
    ];
@endphp

<div class="card-head" style="margin-bottom:8px">
    <div>
        <h1 style="margin:0">Configurações</h1>
        <p class="muted" style="margin:6px 0 0">Aparência, push, e-mail e manutenção — sem perder dados existentes.</p>
    </div>
</div>

<nav class="mail-tabs" style="margin:16px 0 18px;display:flex;gap:8px;flex-wrap:wrap" aria-label="Abas de configuração">
    @foreach($tabs as $key => $meta)
        <a href="{{ route('configuracoes.index', ['tab' => $key]) }}" class="{{ $tab === $key ? 'is-on' : '' }}">
            <b>{{ $meta['label'] }}</b>
            <span class="muted" style="font-weight:600">{{ $meta['hint'] }}</span>
        </a>
    @endforeach
</nav>

@if($tab === 'aparencia')
<form method="post" action="{{ route('configuracoes.update') }}" enctype="multipart/form-data" class="card stack" style="max-width:720px">
    @csrf
    <input type="hidden" name="tab" value="aparencia">

    <label class="muted">Nome da plataforma</label>
    <input class="input" name="empresa" value="{{ old('empresa', $settings['empresa']) }}" required>

    <label class="muted">Subtítulo / documento</label>
    <input class="input" name="documento" value="{{ old('documento', $settings['documento']) }}">

    <label class="muted">Cor primária</label>
    <div style="display:flex;gap:10px;align-items:center">
        <input class="input" type="color" name="cor_primaria" value="{{ old('cor_primaria', $settings['cor_primaria'] ?: '#E2A84A') }}" style="max-width:72px;padding:4px">
        <span class="muted">Usada em destaques do painel</span>
    </div>

    <label class="muted">Logo</label>
    @if(!empty($brand['logo']))
        <div style="display:flex;gap:12px;align-items:center;margin-bottom:6px">
            <img src="{{ $brand['logo'] }}" alt="Logo atual" style="max-height:48px;max-width:160px;border-radius:8px;background:#111;padding:6px">
            <label class="muted" style="display:flex;gap:8px;align-items:center">
                <input type="checkbox" name="remover_logo" value="1"> Remover logo
            </label>
        </div>
    @endif
    <input class="input" type="file" name="logo" accept="image/*">

    <label class="muted">Favicon</label>
    @if(!empty($brand['favicon']))
        <div style="display:flex;gap:12px;align-items:center;margin-bottom:6px">
            <img src="{{ $brand['favicon'] }}" alt="Favicon" style="width:28px;height:28px;border-radius:6px">
            <label class="muted" style="display:flex;gap:8px;align-items:center">
                <input type="checkbox" name="remover_favicon" value="1"> Remover favicon
            </label>
        </div>
    @endif
    <input class="input" type="file" name="favicon" accept=".ico,.png,.jpg,.jpeg,.svg,.webp,image/*">

    <button class="btn" type="submit">Salvar aparência</button>
</form>
@endif

@if($tab === 'push')
<form method="post" action="{{ route('configuracoes.update') }}" class="card stack" style="max-width:720px">
    @csrf
    <input type="hidden" name="tab" value="push">

    <label class="muted">Push ativo</label>
    <select class="input" name="push_ativo">
        <option value="1" @selected((string) old('push_ativo', $settings['push_ativo']) === '1')>Ativado</option>
        <option value="0" @selected((string) old('push_ativo', $settings['push_ativo']) === '0')>Desativado</option>
    </select>

    <label class="muted">Título padrão</label>
    <input class="input" name="push_titulo" value="{{ old('push_titulo', $settings['push_titulo']) }}">

    <label class="muted">Corpo padrão</label>
    <textarea class="input" name="push_corpo" rows="3">{{ old('push_corpo', $settings['push_corpo']) }}</textarea>
    <p class="muted" style="margin:0">Placeholders: <code>{porta}</code> <code>{senha}</code> <code>{morador}</code></p>

    <button class="btn" type="submit">Salvar push</button>
</form>
@endif

@if($tab === 'email')
<form method="post" action="{{ route('configuracoes.update') }}" class="card stack" style="max-width:720px">
    @csrf
    <input type="hidden" name="tab" value="email">

    <label class="muted">Mailer</label>
    <select class="input" name="mail_mailer">
        @foreach(['smtp' => 'SMTP', 'log' => 'Log (desenvolvimento)', 'sendmail' => 'Sendmail'] as $val => $label)
            <option value="{{ $val }}" @selected(old('mail_mailer', $settings['mail_mailer']) === $val)>{{ $label }}</option>
        @endforeach
    </select>

    <div class="meta-grid">
        <div>
            <label class="muted">Host</label>
            <input class="input" name="mail_host" value="{{ old('mail_host', $settings['mail_host']) }}">
        </div>
        <div>
            <label class="muted">Porta</label>
            <input class="input" name="mail_port" value="{{ old('mail_port', $settings['mail_port']) }}">
        </div>
        <div>
            <label class="muted">Usuário</label>
            <input class="input" name="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}">
        </div>
        <div>
            <label class="muted">Senha</label>
            <input class="input" type="password" name="mail_password" value="" placeholder="{{ $settings['mail_password'] ? '•••••••• (deixe vazio para manter)' : 'Senha SMTP' }}">
        </div>
        <div>
            <label class="muted">Criptografia</label>
            <select class="input" name="mail_encryption">
                @foreach(['tls' => 'TLS', 'ssl' => 'SSL', '' => 'Nenhuma'] as $val => $label)
                    <option value="{{ $val }}" @selected((string) old('mail_encryption', $settings['mail_encryption']) === (string) $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="muted">Remetente (e-mail)</label>
            <input class="input" type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address']) }}">
        </div>
    </div>

    <label class="muted">Nome do remetente</label>
    <input class="input" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name']) }}">

    <button class="btn" type="submit">Salvar e-mail</button>
</form>
@endif

@if($tab === 'manutencao')
<div class="card stack" style="max-width:820px;margin-bottom:16px">
    <h3 style="margin:0">Sincronização automática</h3>
    <p class="muted" style="margin:0">O painel agenda o sync sozinho. Ajuste o intervalo abaixo — não é necessário clicar em “Sincronizar agora” no dia a dia.</p>
    <form method="post" action="{{ route('configuracoes.update') }}" class="stack">
        @csrf
        <input type="hidden" name="tab" value="manutencao">

        <label class="muted">Sync automática</label>
        <select class="input" name="sync_automatica">
            <option value="1" @selected((string) old('sync_automatica', $settings['sync_automatica']) === '1')>Ativada</option>
            <option value="0" @selected((string) old('sync_automatica', $settings['sync_automatica']) === '0')>Pausada</option>
        </select>

        <label class="muted">Intervalo (minutos)</label>
        <input class="input" type="number" min="1" max="1440" name="sync_minutos" value="{{ old('sync_minutos', $settings['sync_minutos']) }}">

        <label class="muted">Timezone</label>
        <input class="input" name="timezone" value="{{ old('timezone', $settings['timezone']) }}">

        <button class="btn" type="submit">Salvar sync / timezone</button>
    </form>
</div>

<div class="card stack" style="max-width:820px;margin-bottom:16px">
    <h3 style="margin:0">Migrations seguras</h3>
    <p class="muted" style="margin:0">Executa <code>php artisan migrate</code> — aplica mudanças de schema <b>sem</b> apagar dados (<code>migrate:fresh</code> / wipe não são usados).</p>

    @if(count($pendingMigrations))
        <p><b>{{ count($pendingMigrations) }}</b> migration(s) pendente(s):</p>
        <ul class="muted" style="margin:0;padding-left:18px">
            @foreach($pendingMigrations as $m)
                <li><code>{{ $m }}</code></li>
            @endforeach
        </ul>
    @else
        <p class="muted" style="margin:0">Nenhuma migration pendente.</p>
    @endif

    @if(session('migrate_output'))
        <pre style="white-space:pre-wrap;background:rgba(0,0,0,.25);padding:12px;border-radius:10px;font-size:12px">{{ session('migrate_output') }}</pre>
    @endif

    <form method="post" action="{{ route('configuracoes.migrate') }}" onsubmit="return confirm('Rodar migrations agora? Dados existentes serão preservados.')">
        @csrf
        <button class="btn" type="submit">Rodar migrations</button>
    </form>
</div>

<div class="card stack" style="max-width:820px">
    <h3 style="margin:0">Utilitários</h3>
    <p class="muted" style="margin:0">Storage link: <b>{{ $storageLinked ? 'OK' : 'ausente' }}</b> — necessário para logo/favicon públicos.</p>
    <div class="filters" style="margin:0">
        <form method="post" action="{{ route('configuracoes.cache') }}">@csrf
            <button class="btn ghost" type="submit">Limpar caches</button>
        </form>
        <form method="post" action="{{ route('configuracoes.storage') }}">@csrf
            <button class="btn ghost" type="submit">Garantir storage:link</button>
        </form>
        <a class="btn ghost" href="{{ route('integracao.index') }}">Abrir Integração / Sync</a>
    </div>
</div>
@endif
@endsection
