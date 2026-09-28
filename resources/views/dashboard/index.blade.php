@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
    $userName = explode(' ', auth()->user()->name)[0] ?? 'operador';
@endphp
<header class="page-head">
    <div>
        <p class="eyebrow">Operação em tempo real</p>
        <h1>{{ $hello }}, {{ $userName }}</h1>
        <p class="muted">Painel master Exclusive Locker · atualizado às {{ $generatedAt }}
            @if($lastSync)
                · sync {{ $lastSync->diffForHumans() }}
            @endif
        </p>
    </div>
    <div class="page-head__actions">
        <div class="seg">
            @foreach([7,14,30] as $p)
                <a class="{{ $days === $p ? 'is-on' : '' }}" href="{{ route('dashboard', ['periodo' => $p]) }}">{{ $p }}d</a>
            @endforeach
        </div>
        <form method="post" action="{{ route('integracao.sync') }}">
            @csrf
            <button class="btn ghost" type="submit">Sincronizar API</button>
        </form>
        <a class="btn ghost" href="{{ route('correspondencias.index', ['status' => 'pendente']) }}">Ver pendências</a>
        <a class="btn" href="{{ route('relatorios.index') }}">Exportar</a>
        <label class="live-toggle">
            <input type="checkbox" id="dash-live">
            <span>Auto-atualizar</span>
        </label>
    </div>
</header>

<div class="health-bar">
    <span class="health-dot {{ $syncFresh ? 'is-ok' : 'is-warn' }}"></span>
    <b>{{ $syncFresh ? 'Integração estável' : 'Sincronização atrasada' }}</b>
    @foreach($health as $item)
        <a href="{{ $item['href'] }}">{{ $item['label'] }} <strong>{{ $item['value'] }}</strong></a>
    @endforeach
</div>

<div class="grid kpis dash-kpis">
    @foreach($kpis as $k)
        <a class="card kpi-card" href="{{ $k['href'] }}">
            <div class="kpi-card__top">
                <span>{{ $k['label'] }}</span>
                @if(!is_null($k['delta']))
                    <em class="delta {{ $k['delta'] >= 0 ? 'is-up' : 'is-down' }}">{{ $k['delta'] >= 0 ? '+' : '' }}{{ $k['delta'] }}%</em>
                @endif
            </div>
            <div class="value tone-{{ $k['tone'] }}">
                {{ is_float($k['value']) ? number_format($k['value'], 1, ',', '.') : number_format($k['value'], 0, ',', '.') }}{{ $k['suffix'] ?? '' }}
            </div>
            <div class="hint">{{ $k['hint'] }}</div>
        </a>
    @endforeach
</div>

<div class="grid dash-main">
    <section class="card">
        <div class="card-head">
            <div>
                <h3>Volume operacional</h3>
                <p class="muted">Entradas e retiradas nos últimos {{ $days }} dias</p>
            </div>
        </div>
        <canvas id="chartLine" height="118"></canvas>
    </section>
    <section class="card">
        <div class="card-head">
            <div>
                <h3>Status e ocupação</h3>
                <p class="muted">Distribuição atual da operação</p>
            </div>
        </div>
        <div class="status-split">
            <div class="chart-box"><canvas id="chartPie"></canvas></div>
            <div class="occ">
                <div class="occ-ring" style="--p: {{ min($ocupacao, 100) }}">
                    <div class="occ-ring__inner">
                        <b>{{ number_format($ocupacao, 1, ',', '.') }}%</b>
                        <span>ocupado</span>
                    </div>
                </div>
                <ul>
                    <li><span>Capacidade</span><b>{{ $capacidade }}</b></li>
                    <li><span>Ocupados</span><b>{{ $ocupados }}</b></li>
                    <li><span>Livres</span><b>{{ $livres }}</b></li>
                    <li><span>SLA médio</span><b>{{ $avgHours }}h</b></li>
                </ul>
            </div>
        </div>
    </section>
</div>

<div class="grid dash-ops">
    <section class="card">
        <div class="card-head">
            <h3>Aging das pendências</h3>
            <a class="btn ghost" href="{{ route('correspondencias.index', ['status' => 'pendente']) }}">Fila</a>
        </div>
        @php $agingMax = max($aging['total'], 1); @endphp
        <div class="aging">
            <article>
                <span>Até 24h</span>
                <b>{{ $aging['ate24'] }}</b>
                <i style="width: {{ round($aging['ate24'] / $agingMax * 100) }}%"></i>
            </article>
            <article>
                <span>24–48h</span>
                <b class="tone-warn">{{ $aging['h24'] }}</b>
                <i class="is-warn" style="width: {{ round($aging['h24'] / $agingMax * 100) }}%"></i>
            </article>
            <article>
                <span>Acima de 48h</span>
                <b class="tone-danger">{{ $aging['h48'] }}</b>
                <i class="is-danger" style="width: {{ round($aging['h48'] / $agingMax * 100) }}%"></i>
            </article>
        </div>
        <h4 class="mini-title">Unidades com mais pendências</h4>
        @forelse($topPendencias as $row)
            <div class="rank-row">
                <span>{{ trim(($row->bloco ? 'Bloco '.$row->bloco.' · ' : '').'Apt '.$row->apartamento) }}</span>
                <b>{{ $row->total }}</b>
            </div>
        @empty
            <p class="muted">Nenhuma pendência em aberto.</p>
        @endforelse
    </section>

    <section class="card">
        <div class="card-head">
            <h3>Lockers</h3>
            <a class="btn ghost" href="{{ route('lockers.index') }}">Monitorar</a>
        </div>
        @forelse($lockersList as $locker)
            @php
                $cap = max((int) $locker->total, 1);
                $pct = round(((int) $locker->ocupados / $cap) * 100);
            @endphp
            <a class="locker-row" href="{{ route('lockers.show', $locker) }}">
                <div>
                    <strong>{{ $locker->nome }}</strong>
                    <span class="muted">{{ $locker->condominio?->nome }} · {{ $locker->ocupados }}/{{ $locker->total }}</span>
                </div>
                <span class="badge {{ $locker->status === 'online' ? 'b-ok' : 'b-danger' }}">{{ $locker->status }}</span>
                <div class="meter"><i style="width: {{ min($pct, 100) }}%"></i></div>
            </a>
        @empty
            <p class="muted">Nenhum locker sincronizado.</p>
        @endforelse
    </section>

    <section class="card">
        <div class="card-head">
            <h3>Alertas abertos</h3>
            <a class="btn ghost" href="{{ route('alertas.index') }}">Central</a>
        </div>
        @forelse($alertas as $a)
            <div class="alert-row">
                <span class="badge {{ $a->severidade === 'critical' ? 'b-danger' : ($a->severidade === 'warning' ? 'b-warn' : 'b-mute') }}">{{ $a->severidade }}</span>
                <div>
                    <strong>{{ $a->titulo }}</strong>
                    <p class="muted">{{ \Illuminate\Support\Str::limit($a->mensagem, 90) }}</p>
                </div>
                <form method="post" action="{{ route('alertas.read', $a) }}">
                    @csrf
                    <button class="btn ghost" type="submit">Resolver</button>
                </form>
            </div>
        @empty
            <p class="muted">Nenhum alerta aberto. Integração saudável.</p>
        @endforelse
    </section>
</div>

<section class="card" style="margin-top:16px">
    <div class="card-head">
        <div>
            <h3>Movimentações recentes</h3>
            <p class="muted">Últimas entradas sincronizadas da API</p>
        </div>
        <a class="btn ghost" href="{{ route('historico.index') }}">Histórico</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Entrada</th>
                    <th>Morador</th>
                    <th>Unidade</th>
                    <th>Locker / porta</th>
                    <th>Senha</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($recentes as $m)
                <tr>
                    <td>{{ optional($m->data_entrada)->format('d/m H:i') }}</td>
                    <td>{{ $m->morador_nome }}<br><span class="muted">{{ $m->condominio?->nome }}</span></td>
                    <td>{{ trim(($m->bloco ?? '').' '.($m->apartamento ?? '')) ?: '—' }}</td>
                    <td>{{ $m->tablet_nome }} · {{ $m->porta ?: '—' }}</td>
                    <td class="tone-gold">{{ $m->senha ?: '—' }}</td>
                    <td><span class="badge {{ $m->status === 'retirada' ? 'b-ok' : ($m->status === 'pendente' ? 'b-warn' : 'b-danger') }}">{{ $m->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Sincronize a API para popular o painel.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
@section('scripts')
<script>
const labels = @json($labels);
const entradas = @json($entradas);
const saidas = @json($saidas);
const pie = @json($statusPie);
const chartColor = {
  grid: 'rgba(255,255,255,.06)',
  tick: '#8b97a8',
  gold: '#e2a84a',
  ok: '#6fcf97'
};
Chart.defaults.font.family = 'Manrope, Nunito Sans, sans-serif';
Chart.defaults.color = '#8b97a8';

new Chart(document.getElementById('chartLine'), {
  type: 'line',
  data: {
    labels,
    datasets: [
      { label: 'Entradas', data: entradas, borderColor: chartColor.gold, backgroundColor: 'rgba(226,168,74,.16)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2 },
      { label: 'Retiradas', data: saidas, borderColor: chartColor.ok, backgroundColor: 'rgba(111,207,151,.08)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2 }
    ]
  },
  options: {
    interaction: { mode: 'index', intersect: false },
    plugins: { legend: { labels: { boxWidth: 10, color: '#efe8dc' } } },
    scales: {
      x: { ticks: { color: chartColor.tick }, grid: { display: false } },
      y: { ticks: { color: chartColor.tick }, grid: { color: chartColor.grid }, beginAtZero: true }
    }
  }
});

new Chart(document.getElementById('chartPie'), {
  type: 'doughnut',
  data: {
    labels: Object.keys(pie),
    datasets: [{ data: Object.values(pie), backgroundColor: ['#e2a84a', '#6fcf97', '#ef7a7a', '#8b97a8'], borderWidth: 0, hoverOffset: 4 }]
  },
  options: {
    cutout: '68%',
    plugins: { legend: { position: 'bottom', labels: { color: '#efe8dc', boxWidth: 10 } } }
  }
});

const live = document.getElementById('dash-live');
try { live.checked = localStorage.getItem('el.dash.live') === '1'; } catch (e) {}
live.addEventListener('change', function () {
  try { localStorage.setItem('el.dash.live', live.checked ? '1' : '0'); } catch (e) {}
});
setInterval(function () {
  if (live.checked) window.location.reload();
}, 60000);
</script>
@endsection
