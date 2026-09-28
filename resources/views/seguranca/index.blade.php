@extends('layouts.app')
@section('title','Segurança')
@section('content')
<h1>Segurança da plataforma</h1>
<p class="muted">Autenticação, sessões, papéis e proteção contra acessos não autorizados.</p>
<div class="grid two" style="margin-top:16px">
    <div class="card">
        <h3>Administradores</h3>
        <table><thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th>Ativo</th><th>Último login</th></tr></thead>
        <tbody>@foreach($users as $u)
        <tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->role }}</td><td>{{ $u->is_active?'sim':'não' }}</td><td>{{ optional($u->last_login_at)->format('d/m H:i') }}</td></tr>
        @endforeach</tbody></table>
        <p class="muted" style="margin-top:12px">Senhas com hash bcrypt · sessão regenerada no login · SESSION_ENCRYPT ativo.</p>
    </div>
    <div class="card">
        <h3>Trilha de autenticação</h3>
        @foreach($sessoes as $s)
            <div style="padding:8px 0;border-bottom:1px solid var(--line)">{{ $s->created_at->format('d/m H:i') }} · {{ $s->acao }} · {{ $s->ip }}</div>
        @endforeach
    </div>
</div>
@endsection
