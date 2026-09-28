@extends('layouts.app')
@section('title','Notificações')
@section('content')
<h1>Gestão de notificações push</h1>
<p class="muted">Configuração e monitoramento dos envios automáticos aos moradores.</p>
<form class="card stack" method="post" action="{{ route('notificacoes.store') }}" style="max-width:720px;margin:16px 0">@csrf
    <input class="input" name="titulo" placeholder="Título" required>
    <textarea class="input" name="mensagem" rows="3" placeholder="Mensagem" required></textarea>
    <select class="input" name="canal"><option value="push">Push</option><option value="email">E-mail</option><option value="sms">SMS</option></select>
    <button class="btn">Registrar disparo</button>
</form>
<div class="card table-wrap"><table>
<thead><tr><th>Quando</th><th>Canal</th><th>Título</th><th>Status</th></tr></thead>
<tbody>@foreach($rows as $r)
<tr><td>{{ $r->created_at->format('d/m H:i') }}</td><td>{{ $r->canal }}</td><td>{{ $r->titulo }}</td><td>{{ $r->status }}</td></tr>
@endforeach</tbody></table>{{ $rows->links() }}</div>
@endsection
