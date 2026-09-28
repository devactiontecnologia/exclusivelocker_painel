@extends('layouts.app')
@section('title','Integração')
@section('content')
@php
    $syncOn = (string) \App\Models\Setting::get('sync_automatica', '1') !== '0';
    $syncMin = max(1, (int) \App\Models\Setting::get('sync_minutos', 5));
@endphp
<h1>Integração Exclusive Locker</h1>
<p class="muted">Sincronização automática das movimentações, moradores e ocupação dos lockers.</p>

<div class="card" style="margin:16px 0;border-color:{{ $syncOn ? 'rgba(90,200,140,.35)' : 'rgba(239,122,122,.35)' }}">
    <p style="margin:0 0 6px">
        <b>{{ $syncOn ? 'Sync automática ativa' : 'Sync automática pausada' }}</b>
        — a cada <b>{{ $syncMin }}</b> minuto(s).
        <a href="{{ route('configuracoes.index', ['tab' => 'manutencao']) }}">Ajustar em Configurações</a>
    </p>
    <p class="muted" style="margin:0">No servidor, mantenha o agendador Laravel rodando (`php artisan schedule:work` ou cron `* * * * * php artisan schedule:run`). O botão abaixo é só para sync manual imediata.</p>
</div>

<div class="card" style="margin:16px 0">
    <p>Identidade: <b>{{ $config['identidade'] }}</b></p>
    <p>Catálogo: <b>{{ $config['catalogo'] }}</b></p>
    <p>Android: <b>{{ $config['android'] }}</b></p>
    <p>Conta de serviço: <b>{{ $config['email'] }}</b></p>
    <form method="post" action="{{ route('integracao.sync') }}" class="filters">@csrf
        <select class="input" name="condominio_id">
            <option value="">Todos os condomínios ativos</option>
            @foreach($condominios as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach
        </select>
        <button class="btn">Sincronizar agora</button>
    </form>
</div>
<div class="card table-wrap"><table>
<thead><tr><th>Condomínio</th><th>UUID</th><th>Última sync</th><th>Status</th></tr></thead>
<tbody>@foreach($condominios as $c)
<tr><td>{{ $c->nome }}</td><td class="muted">{{ $c->uuid_agregacao }}</td><td>{{ optional($c->ultima_sincronizacao)->format('d/m/Y H:i') ?: '—' }}</td><td>{{ $c->status }}</td></tr>
@endforeach</tbody></table></div>
@endsection
