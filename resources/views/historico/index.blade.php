@extends('layouts.app')
@section('title', 'Histórico geral')
@section('content')
@php
    $qs = fn (array $extra = []) => array_filter(array_merge(request()->except('page'), $extra), fn ($v) => $v !== null && $v !== '');
    $badge = fn (string $st) => $st === 'retirada' ? 'b-ok' : ($st === 'pendente' ? 'b-warn' : 'b-danger');
@endphp
<header class="page-head">
    <div>
        <p class="eyebrow">Linha do tempo</p>
        <h1>Histórico geral</h1>
        <p class="muted">{{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }} · {{ $days }} dia(s) · {{ number_format($rows->total(), 0, ',', '.') }} correspondências</p>
    </div>
    <div class="page-head__actions">
        <div class="seg">
            @foreach([1 => 'Hoje', 7 => '7d', 14 => '14d', 30 => '30d', 90 => '90d'] as $p => $label)
                <a class="{{ !request()->filled('de') && request('periodo', 14) == $p ? 'is-on' : '' }}" href="{{ route('historico.index', $qs(['periodo' => $p, 'de' => null, 'ate' => null])) }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="seg">
            <a class="{{ $viewMode === 'timeline' ? 'is-on' : '' }}" href="{{ route('historico.index', $qs(['view' => 'timeline'])) }}">Timeline</a>
            <a class="{{ $viewMode === 'tabela' ? 'is-on' : '' }}" href="{{ route('historico.index', $qs(['view' => 'tabela'])) }}">Tabela</a>
        </div>
        <a class="btn ghost" href="{{ route('historico.export', request()->query()) }}">CSV</a>
        <button class="btn" type="button" onclick="window.print()">Imprimir</button>
    </div>
</header>

<div class="grid kpis dash-kpis">
    <article class="card kpi-card">
        <div class="kpi-card__top"><span>Entradas</span></div>
        <div class="value tone-gold">{{ number_format($kpis['entradas'], 0, ',', '.') }}</div>
        <div class="hint">no período</div>
    </article>
    <article class="card kpi-card">
        <div class="kpi-card__top"><span>Retiradas</span></div>
        <div class="value tone-ok">{{ number_format($kpis['retiradas'], 0, ',', '.') }}</div>
        <div class="hint">SLA médio {{ $avgHours }}h</div>
    </article>
    <article class="card kpi-card">
        <div class="kpi-card__top"><span>Ainda pendentes</span></div>
        <div class="value tone-warn">{{ number_format($kpis['pendentes'], 0, ',', '.') }}</div>
        <div class="hint">abertas neste recorte</div>
    </article>
    <article class="card kpi-card">
        <div class="kpi-card__top"><span>Volume diário</span></div>
        <canvas id="spark" height="54"></canvas>
        <div class="hint">{{ $kpis['expiradas'] }} expiradas</div>
    </article>
</div>

<nav class="mail-tabs">
    <a class="{{ !$evento ? 'is-on' : '' }}" href="{{ route('historico.index', $qs(['evento' => null])) }}">Todos os eventos</a>
    <a class="{{ $evento === 'entrada' ? 'is-on' : '' }}" href="{{ route('historico.index', $qs(['evento' => 'entrada'])) }}">Só entradas</a>
    <a class="{{ $evento === 'retirada' ? 'is-on' : '' }}" href="{{ route('historico.index', $qs(['evento' => 'retirada'])) }}">Só retiradas</a>
    <a class="{{ $evento === 'expirada' ? 'is-on' : '' }}" href="{{ route('historico.index', $qs(['evento' => 'expirada'])) }}">Expiradas</a>
</nav>

<form class="filters mail-filters" method="get">
    <input type="hidden" name="periodo" value="{{ request('periodo', 14) }}">
    <input type="hidden" name="view" value="{{ $viewMode }}">
    <input type="hidden" name="evento" value="{{ $evento }}">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Morador, unidade, porta, senha, locker...">
    <select class="input" name="condominio_id">
        <option value="">Todos os condomínios</option>
        @foreach($condominios as $c)
            <option value="{{ $c->id }}" @selected(request('condominio_id') == $c->id)>{{ $c->nome }}</option>
        @endforeach
    </select>
    <select class="input" name="locker">
        <option value="">Todos os lockers</option>
        @foreach($lockers as $l)
            <option value="{{ $l->nome }}" @selected(request('locker') == $l->nome)>{{ $l->nome }}</option>
        @endforeach
    </select>
    <select class="input" name="status">
        <option value="">Status atual</option>
        @foreach(['pendente','retirada','expirada','erro'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
        @endforeach
    </select>
    <input class="input" type="date" name="de" value="{{ request('de', $from->format('Y-m-d')) }}">
    <input class="input" type="date" name="ate" value="{{ request('ate', $to->format('Y-m-d')) }}">
    <button class="btn ghost" type="submit">Filtrar</button>
    <a class="btn ghost" href="{{ route('historico.index') }}">Limpar</a>
</form>

@if($viewMode === 'timeline')
    @forelse($timeline as $dia => $eventos)
        <section class="tl-day">
            <header>
                <b>{{ \Illuminate\Support\Carbon::parse($dia)->translatedFormat('l, d/m/Y') }}</b>
                <span>{{ $eventos->count() }} evento(s)</span>
            </header>
            @foreach($eventos as $e)
                @php $m = $e['m']; @endphp
                <a class="tl-item tl-item--{{ $e['tipo'] }}" href="{{ route('correspondencias.show', $m) }}">
                    <i></i>
                    <time>{{ $e['at']->format('H:i') }}</time>
                    <div class="tl-body">
                        <strong>{{ $e['tipo'] === 'retirada' ? 'Retirada' : ($e['tipo'] === 'expirada' ? 'Expirada' : 'Entrada no locker') }}</strong>
                        <p>{{ $m->morador_nome ?: 'Morador não identificado' }} · {{ trim(($m->bloco ? 'Bloco '.$m->bloco.' · ' : '').($m->apartamento ? 'Apt '.$m->apartamento : '')) ?: 's/ unidade' }}</p>
                        <p class="muted">{{ $m->condominio?->nome }} · {{ $m->tablet_nome ?: 'Locker' }} · Porta {{ $m->porta ?: '—' }}
                            @if($e['tipo'] === 'retirada' && $m->data_entrada)
                                · {{ (int) $m->data_entrada->diffInHours($m->data_retirada) }}h depois da entrada
                            @endif
                        </p>
                    </div>
                    <span class="badge {{ $badge($m->status) }}">{{ $m->status }}</span>
                </a>
            @endforeach
        </section>
    @empty
        <div class="card empty">
            <b>Nenhum evento neste recorte</b>
            <p class="muted">Altere o período, o condomínio ou sincronize a API.</p>
        </div>
    @endforelse
    <div class="table-foot">{{ $rows->links() }}</div>
@else
    <div class="card table-wrap mail-table">
        <table>
            <thead>
                <tr>
                    <th>Entrada</th>
                    <th>Retirada</th>
                    <th>Morador</th>
                    <th>Unidade</th>
                    <th>Locker</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr onclick="window.location='{{ route('correspondencias.show', $r) }}'">
                    <td>{{ optional($r->data_entrada)->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($r->data_retirada)->format('d/m/Y H:i') ?: '—' }}</td>
                    <td>
                        <strong>{{ $r->morador_nome ?: '—' }}</strong>
                        <div class="muted">{{ $r->condominio?->nome }}</div>
                    </td>
                    <td>{{ trim(($r->bloco ? 'Bloco '.$r->bloco.' · ' : '').($r->apartamento ? 'Apt '.$r->apartamento : '')) ?: '—' }}</td>
                    <td>{{ $r->tablet_nome }} · {{ $r->porta }}</td>
                    <td><span class="badge {{ $badge($r->status) }}">{{ $r->status }}</span></td>
                    <td onclick="event.stopPropagation()"><a class="btn ghost" href="{{ route('correspondencias.show', $r) }}">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Nenhum registro.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="table-foot">{{ $rows->links() }}</div>
    </div>
@endif
@endsection
@section('scripts')
<script>
new Chart(document.getElementById('spark'), {
  type: 'bar',
  data: {
    labels: @json($sparkLabels),
    datasets: [{ data: @json($sparkValues), backgroundColor: 'rgba(226,168,74,.75)', borderRadius: 3 }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { x: { display: false }, y: { display: false, beginAtZero: true } },
    maintainAspectRatio: false
  }
});
</script>
@endsection
