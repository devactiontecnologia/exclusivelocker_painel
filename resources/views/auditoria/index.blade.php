@extends('layouts.app')
@section('title','Auditoria')
@section('content')
<h1>Auditoria de acessos</h1>
<p class="muted">Rastreabilidade completa das ações dos administradores.</p>
<form class="filters" method="get"><input class="input" name="q" value="{{ request('q') }}" placeholder="Buscar ação"><button class="btn ghost">Filtrar</button></form>
<div class="card table-wrap"><table>
<thead><tr><th>Quando</th><th>Usuário</th><th>Módulo</th><th>Ação</th><th>IP</th></tr></thead>
<tbody>@foreach($rows as $r)
<tr><td>{{ $r->created_at->format('d/m/Y H:i') }}</td><td>{{ $r->user?->name ?? 'sistema' }}</td><td>{{ $r->modulo }}</td><td>{{ $r->acao }}</td><td class="muted">{{ $r->ip }}</td></tr>
@endforeach</tbody></table>{{ $rows->links() }}</div>
@endsection
