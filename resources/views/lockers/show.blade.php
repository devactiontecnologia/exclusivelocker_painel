@extends('layouts.app')
@section('title', $locker->nome)
@section('content')
@php $pct = $locker->occupancyPct(); @endphp
<header class="page-head">
    <div>
        <p class="eyebrow">{{ $locker->condominio?->nome }}</p>
        <h1>{{ $locker->nome }}</h1>
        <p class="muted">
            Tablet {{ $locker->tablet_id ?: '—' }}
            · atualizado {{ optional($locker->atualizado_em)->diffForHumans() ?: 'nunca' }}
        </p>
    </div>
    <div class="page-head__actions">
        <a class="btn ghost" href="{{ route('lockers.index') }}">Todos os lockers</a>
        <a class="btn ghost" href="{{ route('lockers.export', $locker) }}">CSV</a>
        <a class="btn ghost" href="{{ route('integracao.index') }}">Sincronizar</a>
        <span class="badge {{ $locker->isOnline() ? 'b-ok' : 'b-warn' }}" style="padding:10px 14px">{{ $locker->status }}</span>
    </div>
</header>

<div class="grid kpis dash-kpis">
    <article class="card kpi-card">
        <div class="kpi-card__top"><span>Ocupação</span></div>
        <div class="value tone-gold">{{ number_format($pct, 1, ',', '.') }}%</div>
        <div class="hint">{{ $locker->ocupados }} ocupados · {{ $locker->livres }} livres</div>
        <div class="meter" style="margin-top:10px"><i style="width: {{ min($pct, 100) }}%"></i></div>
    </article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Pendências</span></div><div class="value tone-warn">{{ $kpis['pendentes'] }}</div><div class="hint">portas aguardando retirada</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Retiradas</span></div><div class="value tone-ok">{{ $kpis['retiradas'] }}</div><div class="hint">histórico concluído</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Hoje</span></div><div class="value tone-sand">{{ $kpis['hoje'] }}</div><div class="hint">{{ number_format($kpis['total'], 0, ',', '.') }} no total</div></article>
</div>

<div class="grid dash-main">
    <section class="card">
        <div class="card-head">
            <div>
                <h3>Volume (14 dias)</h3>
                <p class="muted">Entradas associadas a este armário</p>
            </div>
        </div>
        <canvas id="chartLocker" height="120"></canvas>
    </section>
    <section class="card">
        <div class="card-head">
            <h3>Portas pendentes</h3>
            <a class="btn ghost" href="{{ route('correspondencias.index', ['locker' => $locker->nome, 'status' => 'pendente']) }}">Fila</a>
        </div>
        @if($portas->isEmpty())
            <p class="muted">Nenhuma porta ocupada neste locker.</p>
        @else
            <div class="door-grid">
                @foreach($portas as $p)
                    <a class="door-chip" href="{{ route('correspondencias.show', $p) }}" title="{{ $p->morador_nome }}">
                        <b>{{ $p->porta }}</b>
                        <span>{{ \Illuminate\Support\Str::limit($p->morador_nome, 16) }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>

<section class="card" style="margin-top:16px">
    <div class="card-head">
        <div>
            <h3>Movimentações</h3>
            <p class="muted">Histórico operacional deste locker</p>
        </div>
    </div>
    <form class="filters" method="get">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Morador ou porta">
        <select class="input" name="status">
            <option value="">Status</option>
            @foreach(['pendente','retirada','expirada'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
            @endforeach
        </select>
        <button class="btn ghost">Filtrar</button>
    </form>
    <div class="table-wrap mail-table">
        <table>
            <thead><tr><th>Quando</th><th>Morador</th><th>Unidade</th><th>Porta</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($hist as $m)
                <tr onclick="window.location='{{ route('correspondencias.show', $m) }}'">
                    <td>{{ optional($m->data_entrada)->format('d/m/Y H:i') }}</td>
                    <td>{{ $m->morador_nome ?: '—' }}</td>
                    <td>{{ trim(($m->bloco ? 'Bloco '.$m->bloco.' · ' : '').($m->apartamento ? 'Apt '.$m->apartamento : '')) ?: '—' }}</td>
                    <td>{{ $m->porta ?: '—' }}</td>
                    <td><span class="badge {{ $m->status === 'retirada' ? 'b-ok' : ($m->status === 'pendente' ? 'b-warn' : 'b-danger') }}">{{ $m->status }}</span></td>
                    <td onclick="event.stopPropagation()"><a class="btn ghost" href="{{ route('correspondencias.show', $m) }}">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Sem movimentações neste recorte.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-foot">{{ $hist->links() }}</div>
</section>
@endsection
@section('scripts')
<script>
new Chart(document.getElementById('chartLocker'), {
  type: 'bar',
  data: {
    labels: @json($labels),
    datasets: [{ data: @json($values), backgroundColor: 'rgba(226,168,74,.8)', borderRadius: 6 }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: {
      x: { ticks: { color: '#8b97a8' }, grid: { display: false } },
      y: { ticks: { color: '#8b97a8' }, grid: { color: 'rgba(255,255,255,.06)' }, beginAtZero: true }
    }
  }
});
</script>
@endsection
