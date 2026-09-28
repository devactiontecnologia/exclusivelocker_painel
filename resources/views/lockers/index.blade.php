@extends('layouts.app')
@section('title', 'Monitoramento lockers')
@section('content')
<header class="page-head">
    <div>
        <p class="eyebrow">Infraestrutura</p>
        <h1>Monitoramento dos lockers</h1>
        <p class="muted">Ocupação, conectividade e pendências por armário inteligente.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn ghost" href="{{ route('integracao.index') }}">Sincronizar</a>
        <a class="btn" href="{{ route('correspondencias.index', ['status' => 'pendente']) }}">Ver pendências</a>
    </div>
</header>

<div class="grid kpis dash-kpis">
    <article class="card kpi-card"><div class="kpi-card__top"><span>Lockers</span></div><div class="value tone-sand">{{ $summary['total'] }}</div><div class="hint">cadastrados</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Online</span></div><div class="value tone-ok">{{ $summary['online'] }}</div><div class="hint">{{ $summary['offline'] }} fora do ar</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Ocupação média</span></div><div class="value tone-gold">{{ number_format($summary['ocupacao'], 1, ',', '.') }}%</div><div class="hint">taxa da API</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Pendências</span></div><div class="value tone-warn">{{ $summary['pendentes'] }}</div><div class="hint">aguardando retirada</div></article>
</div>

<form class="filters mail-filters" method="get">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Locker, tablet ou condomínio">
    <select class="input" name="condominio_id">
        <option value="">Todos os condomínios</option>
        @foreach($condominios as $c)
            <option value="{{ $c->id }}" @selected(request('condominio_id') == $c->id)>{{ $c->nome }}</option>
        @endforeach
    </select>
    <select class="input" name="status">
        <option value="">Status</option>
        @foreach(['online','offline','indisponivel'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
        @endforeach
    </select>
    <button class="btn ghost">Filtrar</button>
    <a class="btn ghost" href="{{ route('lockers.index') }}">Limpar</a>
</form>

<div class="locker-grid">
    @forelse($rows as $r)
        @php $pct = $r->occupancyPct(); @endphp
        <a class="card locker-card" href="{{ route('lockers.show', $r) }}">
            <div class="locker-card__top">
                <div>
                    <span class="muted">{{ $r->condominio?->nome ?: '—' }}</span>
                    <strong>{{ $r->nome }}</strong>
                </div>
                <span class="badge {{ $r->isOnline() ? 'b-ok' : ($r->isStale() ? 'b-warn' : 'b-danger') }}">{{ $r->isStale() && $r->status === 'online' ? 'stale' : $r->status }}</span>
            </div>
            <div class="locker-card__body">
                <div class="occ-ring" style="--p: {{ min($pct, 100) }}">
                    <div class="occ-ring__inner">
                        <b>{{ number_format($pct, 1, ',', '.') }}%</b>
                        <span>ocupado</span>
                    </div>
                </div>
                <ul>
                    <li><span>Pendências</span><b>{{ $r->pendentes_count }}</b></li>
                    <li><span>Entradas hoje</span><b>{{ $r->hoje_count }}</b></li>
                    <li><span>Volume</span><b>{{ number_format($r->movimentos_count, 0, ',', '.') }}</b></li>
                    <li><span>Atualizado</span><b>{{ optional($r->atualizado_em)->diffForHumans() ?: 'nunca' }}</b></li>
                </ul>
            </div>
            <div class="meter"><i style="width: {{ min($pct, 100) }}%"></i></div>
        </a>
    @empty
        <div class="card empty" style="grid-column:1 / -1">
            <b>Nenhum locker neste filtro</b>
            <p class="muted">Sincronize a ocupação na integração para popular o monitoramento.</p>
            <a class="btn" href="{{ route('integracao.index') }}">Ir para sync</a>
        </div>
    @endforelse
</div>
<div class="table-foot">{{ $rows->links() }}</div>
@endsection
