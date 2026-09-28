@extends('layouts.app')
@section('title','Suporte do app')
@section('content')
<div class="page-head">
    <div>
        <h1>Suporte do app</h1>
        <p class="muted">Mensagens enviadas pelos moradores pelo aplicativo Exclusive Locker.</p>
    </div>
    <div class="kpi-inline">
        <span class="badge b-warn">{{ $abertos }} abertos</span>
    </div>
</div>

<form class="filters" method="get" style="margin:16px 0;display:flex;gap:10px;flex-wrap:wrap">
    <select name="status" onchange="this.form.submit()">
        <option value="">Todos os status</option>
        @foreach(['aberto','em_andamento','resolvido'] as $s)
            <option value="{{ $s }}" @selected($status===$s)>{{ str_replace('_',' ',$s) }}</option>
        @endforeach
    </select>
</form>

<div class="card table-wrap">
<table>
<thead>
<tr>
    <th>#</th>
    <th>Morador</th>
    <th>Condomínio</th>
    <th>Assunto</th>
    <th>Status</th>
    <th>Quando</th>
    <th></th>
</tr>
</thead>
<tbody>
@forelse($rows as $r)
<tr>
    <td>{{ $r->id }}</td>
    <td>
        <strong>{{ $r->account?->nome ?? '—' }}</strong><br>
        <span class="muted">{{ $r->account?->email }}</span>
    </td>
    <td>{{ $r->condominio?->nome ?? '—' }}</td>
    <td>{{ $r->assunto }}</td>
    <td>
        <span class="badge {{ $r->status==='resolvido'?'b-ok':($r->status==='em_andamento'?'b-warn':'b-mute') }}">
            {{ str_replace('_',' ',$r->status) }}
        </span>
    </td>
    <td class="muted">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
    <td><a class="btn ghost" href="{{ route('suporte.show',$r) }}">Abrir</a></td>
</tr>
@empty
<tr><td colspan="7" class="muted">Nenhum ticket ainda.</td></tr>
@endforelse
</tbody>
</table>
{{ $rows->links() }}
</div>
@endsection
