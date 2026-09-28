@extends('layouts.app')
@section('title', 'Condomínios')
@section('content')
@php
    $sortLink = function (string $col) use ($sort, $dir) {
        $next = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $col, 'dir' => $next, 'page' => null]);
    };
    $arrow = fn (string $col) => $sort === $col ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
@endphp
<header class="page-head">
    <div>
        <p class="eyebrow">Cadastros</p>
        <h1>Gestão de condomínios</h1>
        <p class="muted">Identidade, credenciais da API e operação por agregação Exclusive Locker.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn ghost" href="{{ route('integracao.index') }}">Sincronizar</a>
        <a class="btn" href="{{ route('condominios.create') }}">Novo condomínio</a>
    </div>
</header>

<div class="grid kpis dash-kpis">
    <article class="card kpi-card"><div class="kpi-card__top"><span>Total</span></div><div class="value">{{ $summary['total'] }}</div><div class="hint">cadastrados</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Ativos</span></div><div class="value tone-ok">{{ $summary['ativos'] }}</div><div class="hint">em operação</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Bloqueados</span></div><div class="value tone-warn">{{ $summary['bloqueados'] }}</div><div class="hint">acesso suspenso</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Sync recente</span></div><div class="value tone-gold">{{ $summary['sync'] }}</div><div class="hint">últimos 15 min</div></article>
</div>

<form class="filters mail-filters" method="get">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Nome, cidade, CNPJ ou UUID">
    <select class="input" name="status">
        <option value="">Todos os status</option>
        @foreach(['ativo','bloqueado','removido'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
        @endforeach
    </select>
    <button class="btn ghost">Filtrar</button>
    <a class="btn ghost" href="{{ route('condominios.index') }}">Limpar</a>
</form>

<div class="card table-wrap mail-table">
    <table>
        <thead>
            <tr>
                <th><a href="{{ $sortLink('nome') }}">Condomínio{{ $arrow('nome') }}</a></th>
                <th>Agregação</th>
                <th><a href="{{ $sortLink('cidade') }}">Cidade{{ $arrow('cidade') }}</a></th>
                <th>Base</th>
                <th><a href="{{ $sortLink('sync') }}">Último sync{{ $arrow('sync') }}</a></th>
                <th><a href="{{ $sortLink('status') }}">Status{{ $arrow('status') }}</a></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
            <tr onclick="window.location='{{ route('condominios.show', $r) }}'">
                <td>
                    <strong>{{ $r->nome }}</strong>
                    <div class="muted">{{ $r->cnpj ?: 'CNPJ não informado' }}</div>
                </td>
                <td onclick="event.stopPropagation()">
                    <code class="mono-id">{{ \Illuminate\Support\Str::limit($r->uuid_agregacao, 18) }}</code>
                    <button type="button" class="icon-mini" data-copy="{{ $r->uuid_agregacao }}" title="Copiar UUID">⎘</button>
                </td>
                <td>{{ $r->cidade ?: '—' }} {{ $r->uf }}</td>
                <td class="muted">{{ $r->moradores_count }} mor. · {{ $r->unidades_count }} un. · {{ $r->lockers_count }} lock.</td>
                <td>{{ optional($r->ultima_sincronizacao)->diffForHumans() ?: 'nunca' }}</td>
                <td><span class="badge {{ $r->status === 'ativo' ? 'b-ok' : ($r->status === 'bloqueado' ? 'b-warn' : 'b-mute') }}">{{ $r->status }}</span></td>
                <td class="actions" onclick="event.stopPropagation()">
                    <a class="btn ghost" href="{{ route('condominios.show', $r) }}">Ver</a>
                    <a class="btn ghost" href="{{ route('condominios.edit', $r) }}">Editar</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">
                    <div class="empty">
                        <b>Nenhum condomínio neste filtro</b>
                        <p class="muted">Cadastre a agregação e as credenciais da API para começar o sync.</p>
                        <a class="btn" href="{{ route('condominios.create') }}">Novo condomínio</a>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
    <div class="table-foot">{{ $rows->links() }}</div>
</div>
@endsection
@section('scripts')
<script>
document.querySelectorAll('[data-copy]').forEach(function (btn) {
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    navigator.clipboard.writeText(btn.getAttribute('data-copy') || '').then(function () {
      btn.textContent = 'ok';
      setTimeout(function () { btn.textContent = '⎘'; }, 900);
    });
  });
});
</script>
@endsection
