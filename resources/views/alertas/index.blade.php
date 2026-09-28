@extends('layouts.app')
@section('title','Alertas')
@section('content')
<h1>Central de alertas</h1>
<p class="muted">Falhas de integração, lockers indisponíveis e ocorrências operacionais.</p>
<div class="card table-wrap" style="margin-top:16px"><table>
<thead><tr><th>Severidade</th><th>Título</th><th>Mensagem</th><th></th></tr></thead>
<tbody>@foreach($rows as $r)
<tr>
    <td><span class="badge {{ $r->severidade==='critical'?'b-danger':($r->severidade==='warning'?'b-warn':'b-mute') }}">{{ $r->severidade }}</span></td>
    <td>{{ $r->titulo }}</td>
    <td class="muted">{{ $r->mensagem }}</td>
    <td>@if(!$r->lido)<form method="post" action="{{ route('alertas.read',$r) }}">@csrf<button class="btn ghost">Resolver</button></form>@else<span class="muted">lido</span>@endif</td>
</tr>
@endforeach</tbody></table>{{ $rows->links() }}</div>
@endsection
