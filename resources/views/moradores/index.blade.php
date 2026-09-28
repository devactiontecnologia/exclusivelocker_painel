@extends('layouts.app')
@section('title', 'Moradores')
@section('content')
<header class="page-head">
    <div>
        <p class="eyebrow">Cadastros</p>
        <h1>Gestão de moradores</h1>
        <p class="muted">Cadastros sincronizados da API — busca, bloqueio e histórico de correspondências.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn ghost" href="{{ route('moradores.export', request()->query()) }}">CSV</a>
        <a class="btn ghost" href="{{ route('integracao.index') }}">Sincronizar</a>
    </div>
</header>

<div class="grid kpis dash-kpis">
    <article class="card kpi-card"><div class="kpi-card__top"><span>Total</span></div><div class="value">{{ number_format($summary['total'], 0, ',', '.') }}</div><div class="hint">cadastros</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Ativos</span></div><div class="value tone-ok">{{ number_format($summary['ativos'], 0, ',', '.') }}</div><div class="hint">com acesso</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Bloqueados</span></div><div class="value tone-warn">{{ $summary['bloqueados'] }}</div><div class="hint">suspensos</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Excluídos</span></div><div class="value tone-danger">{{ $summary['excluidos'] }}</div><div class="hint">baixa lógica</div></article>
</div>

<form class="filters mail-filters" method="get">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Nome, telefone, bloco ou apto">
    <select class="input" name="condominio_id">
        <option value="">Todos os condomínios</option>
        @foreach($condominios as $c)
            <option value="{{ $c->id }}" @selected(request('condominio_id') == $c->id)>{{ $c->nome }}</option>
        @endforeach
    </select>
    <select class="input" name="status">
        <option value="">Todos os status</option>
        @foreach(['ativo','bloqueado','excluido'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
        @endforeach
    </select>
    <button class="btn ghost">Filtrar</button>
    <a class="btn ghost" href="{{ route('moradores.index') }}">Limpar</a>
</form>

<div class="card table-wrap mail-table">
    <table>
        <thead>
            <tr>
                <th>Morador</th>
                <th>Contato</th>
                <th>Unidade</th>
                <th>Condomínio</th>
                <th>Fila</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
            <tr onclick="window.location='{{ route('moradores.show', $r) }}'">
                <td>
                    <strong>{{ $r->nome }}</strong>
                    @if($r->pendentes_count)
                        <div class="muted">{{ $r->pendentes_count }} pendência(s)</div>
                    @endif
                </td>
                <td>{{ $r->telefone ?: '—' }}</td>
                <td>{{ $r->unidade?->label() ?: '—' }}</td>
                <td>{{ $r->condominio?->nome }}</td>
                <td>
                    @if($r->pendentes_count)
                        <span class="badge b-warn">{{ $r->pendentes_count }}</span>
                    @else
                        <span class="muted">—</span>
                    @endif
                </td>
                <td><span class="badge {{ $r->status === 'ativo' ? 'b-ok' : ($r->status === 'excluido' ? 'b-mute' : 'b-warn') }}">{{ $r->status }}</span></td>
                <td class="actions" onclick="event.stopPropagation()">
                    <a class="btn ghost" href="{{ route('moradores.show', $r) }}">Ficha</a>
                    <a class="btn ghost" href="{{ route('moradores.edit', $r) }}">Editar</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">
                    <div class="empty">
                        <b>Nenhum morador neste filtro</b>
                        <p class="muted">Sincronize o condomínio ou ajuste a busca.</p>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
    <div class="table-foot">
        <span class="muted">Mostrando {{ $rows->firstItem() ?: 0 }}–{{ $rows->lastItem() ?: 0 }} de {{ number_format($rows->total(), 0, ',', '.') }}</span>
        {{ $rows->links() }}
    </div>
</div>
@endsection
