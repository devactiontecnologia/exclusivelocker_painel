@extends('layouts.app')
@section('title', 'Painel estatístico')
@section('content')
@php
    $qs = fn (array $extra = []) => array_filter(array_merge(['periodo' => $days, 'condominio_id' => $condoId], $extra));
    $funnelValues = array_column($funnel, 'value') ?: [1];
    $funnelMax = (int) max($funnelValues) ?: 1;
@endphp
<header class="page-head">
    <div>
        <p class="eyebrow">Inteligência operacional</p>
        <h1>Painel estatístico</h1>
        <p class="muted">Volume, SLA, horários de pico e ranking por unidade — últimos {{ $days }} dias.</p>
    </div>
    <div class="page-head__actions">
        <form method="get" class="stats-filters">
            <input type="hidden" name="periodo" value="{{ $days }}">
            <select class="input" name="condominio_id" onchange="this.form.submit()">
                <option value="">Todos os condomínios</option>
                @foreach($condominios as $c)
                    <option value="{{ $c->id }}" @selected($condoId === $c->id)>{{ $c->nome }}</option>
                @endforeach
            </select>
        </form>
        <div class="seg">
            @foreach([7, 14, 30, 90] as $p)
                <a class="{{ $days === $p ? 'is-on' : '' }}" href="{{ route('estatisticas', $qs(['periodo' => $p])) }}">{{ $p }}d</a>
            @endforeach
        </div>
        <a class="btn ghost" href="{{ route('estatisticas.csv', $qs()) }}">CSV</a>
        <button class="btn ghost" type="button" onclick="window.print()">Imprimir</button>
        <a class="btn" href="{{ route('relatorios.index') }}">Relatórios</a>
    </div>
</header>

<div class="grid kpis dash-kpis">
    @foreach($kpis as $k)
        <article class="card kpi-card">
            <div class="kpi-card__top">
                <span>{{ $k['label'] }}</span>
                @if(!is_null($k['delta']))
                    <em class="delta {{ $k['delta'] >= 0 ? 'is-up' : 'is-down' }}">{{ $k['delta'] >= 0 ? '+' : '' }}{{ $k['delta'] }}%</em>
                @endif
            </div>
            <div class="value tone-{{ $k['tone'] }}">{{ $k['value'] }}</div>
            <div class="hint">{{ $k['hint'] }}</div>
        </article>
    @endforeach
</div>

<section class="card funnel-card">
    <div class="card-head">
        <div>
            <h3>Funil do período</h3>
            <p class="muted">Como as {{ number_format($entradasTotal, 0, ',', '.') }} entradas se distribuem hoje</p>
        </div>
    </div>
    <div class="funnel">
        @foreach($funnel as $step)
            <article>
                <span>{{ $step['label'] }}</span>
                <b class="tone-{{ $step['tone'] }}">{{ number_format($step['value'], 0, ',', '.') }}</b>
                <i class="is-{{ $step['tone'] }}" style="width: {{ $funnelMax ? round($step['value'] / $funnelMax * 100) : 0 }}%"></i>
            </article>
        @endforeach
    </div>
</section>

<div class="grid dash-main" style="margin-top:16px">
    <section class="card">
        <div class="card-head">
            <div>
                <h3>Entradas × retiradas</h3>
                <p class="muted">Série diária com dias zerados preenchidos</p>
            </div>
        </div>
        <canvas id="chartLine" height="120"></canvas>
    </section>
    <section class="card">
        <div class="card-head">
            <div>
                <h3>Status do período</h3>
                <p class="muted">Composição das correspondências de entrada</p>
            </div>
        </div>
        <canvas id="chartPie" height="180"></canvas>
    </section>
</div>

<div class="grid dash-main" style="margin-top:16px">
    <section class="card">
        <div class="card-head">
            <div>
                <h3>Volume por hora</h3>
                <p class="muted">Identifica janelas de maior movimento</p>
            </div>
        </div>
        <canvas id="chartHour" height="120"></canvas>
    </section>
    <section class="card">
        <div class="card-head">
            <div>
                <h3>Volume por dia da semana</h3>
                <p class="muted">Padrão semanal da operação</p>
            </div>
        </div>
        <canvas id="chartWeek" height="120"></canvas>
    </section>
</div>

<section class="card" style="margin-top:16px">
    <div class="card-head">
        <div>
            <h3>Mapa de calor — dia × hora</h3>
            <p class="muted">Quanto mais dourado, maior o volume de entradas</p>
        </div>
    </div>
    <div class="heat-wrap">
        <div class="heat">
            <span></span>
            @for($h = 0; $h < 24; $h++)
                <small>{{ sprintf('%02d', $h) }}</small>
            @endfor
            @foreach($weekdayLabels as $d => $label)
                <strong>{{ $label }}</strong>
                @for($h = 0; $h < 24; $h++)
                    @php $v = $heat[$d][$h] ?? 0; $p = $heatMax ? $v / $heatMax : 0; @endphp
                    <b title="{{ $label }} {{ sprintf('%02d', $h) }}h · {{ $v }}" style="background: rgba(226,168,74, {{ $v ? max(0.18, $p) : 0.05 }})"></b>
                @endfor
            @endforeach
        </div>
    </div>
</section>

<div class="grid dash-main" style="margin-top:16px">
    <section class="card">
        <div class="card-head">
            <div>
                <h3>Distribuição de SLA</h3>
                <p class="muted">Tempo entre entrada e retirada</p>
            </div>
        </div>
        <canvas id="chartSla" height="140"></canvas>
    </section>
    <section class="card">
        <div class="card-head">
            <div>
                <h3>Ocupação atual dos lockers</h3>
                <p class="muted">Snapshot da capacidade sincronizada</p>
            </div>
        </div>
        @forelse($lockers as $locker)
            @php $pct = $locker->total ? round($locker->ocupados / max($locker->total, 1) * 100) : 0; @endphp
            <div class="locker-row">
                <div>
                    <strong>{{ $locker->nome }}</strong>
                    <span class="muted">{{ $locker->ocupados }}/{{ $locker->total }} · {{ $locker->taxa_ocupacao ?: $pct.'%' }}</span>
                </div>
                <span class="badge {{ $locker->status === 'online' ? 'b-ok' : 'b-danger' }}">{{ $locker->status }}</span>
                <div class="meter"><i style="width: {{ min($pct, 100) }}%"></i></div>
            </div>
        @empty
            <p class="muted">Nenhum locker no filtro atual.</p>
        @endforelse
    </section>
</div>

<div class="grid stats-tables">
    <section class="card">
        <div class="card-head"><h3>Unidades com maior volume</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Unidade</th><th>Volume</th><th>Pendentes</th></tr></thead>
                <tbody>
                @forelse($topUnidades as $row)
                    <tr>
                        <td>{{ trim(($row->bloco ? 'Bloco '.$row->bloco.' · ' : '').'Apt '.$row->apartamento) }}</td>
                        <td>{{ $row->total }}</td>
                        <td><span class="badge {{ $row->pendentes ? 'b-warn' : 'b-ok' }}">{{ $row->pendentes }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Sem dados no período.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="card">
        <div class="card-head"><h3>Lockers no período</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Locker</th><th>Entradas</th><th>Pendentes</th></tr></thead>
                <tbody>
                @forelse($topLockers as $row)
                    <tr>
                        <td>{{ $row->tablet_nome }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ $row->pendentes }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Sem dados no período.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="card">
        <div class="card-head"><h3>Entregadores</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nome</th><th>Entregas</th></tr></thead>
                <tbody>
                @forelse($topEntregadores as $row)
                    <tr>
                        <td>{{ $row->entregador }}</td>
                        <td>{{ $row->total }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="muted">A API não enviou entregadores neste recorte.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
@section('scripts')
<script>
const labels = @json($labels);
const entradas = @json($entradas);
const saidas = @json($saidas);
const hourly = @json($hourly);
const weekdays = @json($weekdays);
const weekdayLabels = @json($weekdayLabels);
const pie = @json($statusPie);
const buckets = @json($buckets);
const tick = '#8b97a8';
const grid = 'rgba(255,255,255,.06)';
Chart.defaults.font.family = 'Manrope, Nunito Sans, sans-serif';
Chart.defaults.color = tick;
const commonScale = {
  x: { ticks: { color: tick }, grid: { display: false } },
  y: { ticks: { color: tick }, grid: { color: grid }, beginAtZero: true }
};

new Chart(document.getElementById('chartLine'), {
  type: 'line',
  data: {
    labels,
    datasets: [
      { label: 'Entradas', data: entradas, borderColor: '#e2a84a', backgroundColor: 'rgba(226,168,74,.14)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2 },
      { label: 'Retiradas', data: saidas, borderColor: '#6fcf97', backgroundColor: 'rgba(111,207,151,.08)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2 }
    ]
  },
  options: { interaction: { mode: 'index', intersect: false }, plugins: { legend: { labels: { color: '#efe8dc', boxWidth: 10 } } }, scales: commonScale }
});
new Chart(document.getElementById('chartPie'), {
  type: 'doughnut',
  data: { labels: Object.keys(pie), datasets: [{ data: Object.values(pie), backgroundColor: ['#e2a84a','#6fcf97','#ef7a7a','#8b97a8'], borderWidth: 0 }] },
  options: { cutout: '68%', plugins: { legend: { position: 'bottom', labels: { color: '#efe8dc', boxWidth: 10 } } } }
});
new Chart(document.getElementById('chartHour'), {
  type: 'bar',
  data: { labels: hourly.map((_, i) => String(i).padStart(2,'0')+'h'), datasets: [{ data: hourly, backgroundColor: 'rgba(226,168,74,.75)', borderRadius: 6 }] },
  options: { plugins: { legend: { display: false } }, scales: commonScale }
});
new Chart(document.getElementById('chartWeek'), {
  type: 'bar',
  data: { labels: weekdayLabels, datasets: [{ data: weekdays, backgroundColor: 'rgba(111,207,151,.8)', borderRadius: 6 }] },
  options: { plugins: { legend: { display: false } }, scales: commonScale }
});
new Chart(document.getElementById('chartSla'), {
  type: 'bar',
  data: { labels: Object.keys(buckets), datasets: [{ data: Object.values(buckets), backgroundColor: ['#6fcf97','#8fd4a8','#e2a84a','#f2c14e','#ef7a7a'], borderRadius: 6 }] },
  options: { plugins: { legend: { display: false } }, scales: commonScale }
});
</script>
@endsection
