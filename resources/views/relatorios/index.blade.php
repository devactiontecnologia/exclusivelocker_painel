@extends('layouts.app')
@section('title','Relatórios')
@section('content')
<h1>Relatórios gerenciais</h1>
<p class="muted">Entregas, retiradas, pendências, tempo médio e utilização dos lockers.</p>
<div class="grid kpis" style="margin:16px 0">
    <div class="card kpi"><div class="muted">Pendências</div><div class="value tone-warn">{{ $pendentes }}</div></div>
    <div class="card kpi"><div class="muted">Retiradas</div><div class="value tone-ok">{{ $retiradas }}</div></div>
    <div class="card kpi"><div class="muted">Tempo médio</div><div class="value tone-gold">{{ $avg }}h</div></div>
    <div class="card kpi"><div class="muted">Lockers</div><div class="value">{{ $lockers->count() }}</div></div>
</div>
<div class="actions">
    <a class="btn" href="{{ route('relatorios.csv') }}">Exportar Excel/CSV</a>
    <a class="btn ghost" href="{{ route('relatorios.pdf') }}" target="_blank">Exportar PDF</a>
</div>
<div class="card table-wrap" style="margin-top:16px"><table>
<thead><tr><th>Locker</th><th>Taxa</th><th>Status</th><th>Atualizado</th></tr></thead>
<tbody>@foreach($lockers as $l)
<tr><td>{{ $l->nome }}</td><td>{{ $l->taxa_ocupacao }}</td><td>{{ $l->status }}</td><td>{{ optional($l->atualizado_em)->format('d/m H:i') }}</td></tr>
@endforeach</tbody></table></div>
@endsection
