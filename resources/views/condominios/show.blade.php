@extends('layouts.app')
@section('title', $item->nome)
@section('content')
@php $badge = $item->status === 'ativo' ? 'b-ok' : ($item->status === 'bloqueado' ? 'b-warn' : 'b-mute'); @endphp
<header class="page-head">
    <div>
        <p class="eyebrow">Condomínio</p>
        <h1>{{ $item->nome }}</h1>
        <p class="muted">{{ $item->cidade ? $item->cidade.' '.$item->uf : 'Cidade não informada' }} · sync {{ optional($item->ultima_sincronizacao)->diffForHumans() ?: 'nunca' }}</p>
    </div>
    <div class="page-head__actions">
        <a class="btn ghost" href="{{ route('condominios.index') }}">Lista</a>
        <a class="btn ghost" href="{{ route('condominios.edit', $item) }}">Editar</a>
        <form method="post" action="{{ route('condominios.test', $item) }}">@csrf<button class="btn ghost">Testar API</button></form>
        <form method="post" action="{{ route('integracao.sync') }}">@csrf<input type="hidden" name="condominio_id" value="{{ $item->id }}"><button class="btn">Sincronizar agora</button></form>
        <span class="badge {{ $badge }}" style="padding:10px 14px">{{ $item->status }}</span>
    </div>
</header>

<div class="grid kpis dash-kpis">
    <a class="card kpi-card" href="{{ route('moradores.index', ['condominio_id' => $item->id]) }}"><div class="kpi-card__top"><span>Moradores</span></div><div class="value tone-gold">{{ $item->moradores_count }}</div><div class="hint">cadastros</div></a>
    <a class="card kpi-card" href="{{ route('unidades.index', ['condominio_id' => $item->id]) }}"><div class="kpi-card__top"><span>Unidades</span></div><div class="value">{{ $item->unidades_count }}</div><div class="hint">blocos e aptos</div></a>
    <a class="card kpi-card" href="{{ route('lockers.index', ['condominio_id' => $item->id]) }}"><div class="kpi-card__top"><span>Lockers</span></div><div class="value">{{ $item->lockers_count }}</div><div class="hint">armários</div></a>
    <a class="card kpi-card" href="{{ route('correspondencias.index', ['condominio_id' => $item->id, 'status' => 'pendente']) }}"><div class="kpi-card__top"><span>Pendências</span></div><div class="value tone-warn">{{ $pendentes }}</div><div class="hint">{{ $item->movimentos_count }} movimentos</div></a>
</div>

<div class="grid dash-main">
    <section class="card">
        <div class="card-head"><h3>Identidade</h3></div>
        <dl class="meta-grid">
            <div><span>UUID agregação</span><b class="mono-id">{{ $item->uuid_agregacao }}</b></div>
            <div><span>CNPJ</span><b>{{ $item->cnpj ?: '—' }}</b></div>
            <div><span>Endereço</span><b>{{ $item->endereco ?: '—' }}</b></div>
            <div><span>E-mail API</span><b>{{ $item->api_email ?: 'credencial global' }}</b></div>
        </dl>
    </section>
    <section class="card">
        <div class="card-head"><h3>Lockers</h3></div>
        @forelse($item->lockers as $locker)
            <a class="rank-row" href="{{ route('lockers.show', $locker) }}">
                <span>{{ $locker->nome }}</span>
                <b>{{ $locker->status }}</b>
            </a>
        @empty
            <p class="muted">Nenhum locker sincronizado.</p>
        @endforelse
    </section>
</div>

<section class="card" style="margin-top:16px">
    <div class="card-head">
        <h3>Movimentações recentes</h3>
        <a class="btn ghost" href="{{ route('historico.index', ['condominio_id' => $item->id]) }}">Histórico</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Entrada</th><th>Morador</th><th>Porta</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($recentes as $m)
                <tr>
                    <td>{{ optional($m->data_entrada)->format('d/m H:i') }}</td>
                    <td>{{ $m->morador_nome }}</td>
                    <td>{{ $m->porta }}</td>
                    <td><span class="badge {{ $m->status === 'retirada' ? 'b-ok' : ($m->status === 'pendente' ? 'b-warn' : 'b-danger') }}">{{ $m->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Sincronize para ver movimentos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="actions" style="margin-top:14px">
        <form method="post" action="{{ route('condominios.block', $item) }}">@csrf
            <button class="btn ghost">{{ in_array($item->status, ['bloqueado','removido'], true) ? 'Reativar' : 'Bloquear' }}</button>
        </form>
        @if($item->status !== 'removido')
        <form method="post" action="{{ route('condominios.destroy', $item) }}" onsubmit="return confirm('Marcar este condomínio como removido?')">
            @csrf @method('delete')
            <button class="btn danger">Remover</button>
        </form>
        @endif
    </div>
</section>
@endsection
