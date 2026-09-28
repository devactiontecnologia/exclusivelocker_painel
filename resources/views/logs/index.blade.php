@extends('layouts.app')
@section('title','Logs')
@section('content')
<h1>Logs do sistema</h1>
<p class="muted">Eventos técnicos e operacionais da saúde da aplicação.</p>
<div class="card table-wrap" style="margin-top:16px"><table>
<thead><tr><th>Quando</th><th>Nível</th><th>Canal</th><th>Mensagem</th></tr></thead>
<tbody>@foreach($rows as $r)
<tr><td>{{ $r->created_at->format('d/m H:i') }}</td><td>{{ $r->level }}</td><td>{{ $r->canal }}</td><td>{{ $r->mensagem }}</td></tr>
@endforeach</tbody></table>{{ $rows->links() }}</div>
@endsection
