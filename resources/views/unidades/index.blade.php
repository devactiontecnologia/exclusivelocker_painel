@extends('layouts.app')
@section('title','Unidades')
@section('content')
<h1>Gestão de unidades</h1>
<p class="muted">Blocos e apartamentos vinculados aos moradores.</p>
<form class="card filters" method="post" action="{{ route('unidades.store') }}">@csrf
    <select class="input" name="condominio_id" required>@foreach($condominios as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach</select>
    <input class="input" name="bloco" placeholder="Bloco">
    <input class="input" name="apartamento" placeholder="Apartamento" required>
    <button class="btn">Cadastrar unidade</button>
</form>
<form class="filters" method="get">
    <select class="input" name="condominio_id"><option value="">Condomínio</option>@foreach($condominios as $c)<option value="{{ $c->id }}" @selected(request('condominio_id')==$c->id)>{{ $c->nome }}</option>@endforeach</select>
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Bloco / apto">
    <button class="btn ghost">Filtrar</button>
</form>
<div class="card table-wrap"><table>
<thead><tr><th>Condomínio</th><th>Unidade</th><th>Moradores</th><th>Status</th></tr></thead>
<tbody>@foreach($rows as $r)
<tr><td>{{ $r->condominio?->nome }}</td><td>{{ $r->label() }}</td><td>{{ $r->moradores_count }}</td><td>{{ $r->status }}</td></tr>
@endforeach</tbody></table>{{ $rows->links() }}</div>
@endsection
