@extends('layouts.app')
@section('title', $titulo)
@section('content')
@php
    $tab = request()->filled('status') ? request('status') : null;
    $faixa = request()->filled('faixa') ? request('faixa') : null;
    $sortLink = function (string $col) use ($sort, $dir) {
        $next = ($sort === $col && $dir === 'desc') ? 'asc' : 'desc';
        return request()->fullUrlWithQuery(['sort' => $col, 'dir' => $next, 'page' => null]);
    };
    $arrow = fn (string $col) => $sort === $col ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
    $badge = fn (string $st) => $st === 'retirada' ? 'b-ok' : ($st === 'pendente' ? 'b-warn' : 'b-danger');
@endphp
<header class="page-head">
    <div>
        <p class="eyebrow">{{ $modo === 'historico' ? 'Linha do tempo' : 'Fila operacional' }}</p>
        <h1>{{ $titulo }}</h1>
        <p class="muted">
            {{ number_format($rows->total(), 0, ',', '.') }} registros neste recorte
            · senha oculta na listagem · clique na linha para o detalhe
        </p>
    </div>
    <div class="page-head__actions">
        <a class="btn ghost" href="{{ route('integracao.index') }}">Sincronizar</a>
        <a class="btn ghost" href="{{ route('correspondencias.export', request()->query()) }}">CSV filtrado</a>
        <a class="btn" href="{{ route('relatorios.pdf', request()->query()) }}" target="_blank">Imprimir</a>
    </div>
</header>

<nav class="mail-tabs">
    <a class="{{ $tab === null && $faixa === null ? 'is-on' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => null, 'faixa' => null, 'page' => null]) }}">Todas <b>{{ number_format($counts['todos'], 0, ',', '.') }}</b></a>
    <a class="{{ $tab === 'pendente' && $faixa !== 'atrasada' ? 'is-on' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => 'pendente', 'faixa' => null, 'page' => null]) }}">Pendentes <b>{{ $counts['pendente'] }}</b></a>
    <a class="{{ $faixa === 'atrasada' ? 'is-on' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => null, 'faixa' => 'atrasada', 'page' => null]) }}">Atrasadas 24h+ <b>{{ $counts['atrasada'] }}</b></a>
    <a class="{{ $tab === 'retirada' ? 'is-on' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => 'retirada', 'faixa' => null, 'page' => null]) }}">Retiradas <b>{{ $counts['retirada'] }}</b></a>
    <a class="{{ $tab === 'expirada' ? 'is-on' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => 'expirada', 'faixa' => null, 'page' => null]) }}">Expiradas <b>{{ $counts['expirada'] }}</b></a>
    <a class="{{ $faixa === 'hoje' ? 'is-on' : '' }}" href="{{ request()->fullUrlWithQuery(['faixa' => 'hoje', 'page' => null]) }}">Hoje</a>
</nav>

<form class="filters mail-filters" method="get">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <input type="hidden" name="faixa" value="{{ request('faixa') }}">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Morador, unidade, senha, porta, código, locker...">
    <select class="input" name="condominio_id">
        <option value="">Todos os condomínios</option>
        @foreach($condominios as $c)
            <option value="{{ $c->id }}" @selected(request('condominio_id') == $c->id)>{{ $c->nome }}</option>
        @endforeach
    </select>
    <select class="input" name="locker">
        <option value="">Todos os lockers</option>
        @foreach($lockers as $l)
            <option value="{{ $l->nome }}" @selected(request('locker') == $l->nome)>{{ $l->nome }}</option>
        @endforeach
    </select>
    <input class="input" type="date" name="de" value="{{ request('de') }}" title="De">
    <input class="input" type="date" name="ate" value="{{ request('ate') }}" title="Até">
    <select class="input" name="per_page">
        @foreach([25, 50, 100] as $n)
            <option value="{{ $n }}" @selected((int) request('per_page', 25) === $n)>{{ $n }}/pág</option>
        @endforeach
    </select>
    <button class="btn ghost" type="submit">Filtrar</button>
    <a class="btn ghost" href="{{ $modo === 'historico' ? route('historico.index') : route('correspondencias.index') }}">Limpar</a>
</form>

<div class="card table-wrap mail-table">
    <table>
        <thead>
            <tr>
                <th><a href="{{ $sortLink('data_entrada') }}">Entrada{{ $arrow('data_entrada') }}</a></th>
                <th><a href="{{ $sortLink('morador') }}">Morador{{ $arrow('morador') }}</a></th>
                <th><a href="{{ $sortLink('unidade') }}">Unidade{{ $arrow('unidade') }}</a></th>
                <th><a href="{{ $sortLink('locker') }}">Locker / porta{{ $arrow('locker') }}</a></th>
                <th>Senha</th>
                <th>Espera</th>
                <th><a href="{{ $sortLink('status') }}">Status{{ $arrow('status') }}</a></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
            @php
                $espera = '—';
                $atrasada = false;
                if ($r->data_entrada && $r->data_retirada) {
                    $espera = ((int) $r->data_entrada->diffInHours($r->data_retirada)).'h até retirar';
                } elseif ($r->data_entrada && $r->status === 'pendente') {
                    $h = (int) $r->data_entrada->diffInHours(now());
                    $espera = $h.'h na fila';
                    $atrasada = $h >= 24;
                }
            @endphp
            <tr class="{{ $atrasada ? 'is-late' : '' }}" onclick="window.location='{{ route('correspondencias.show', $r) }}'">
                <td>
                    <strong>{{ optional($r->data_entrada)->format('d/m/Y') }}</strong>
                    <div class="muted">{{ optional($r->data_entrada)->format('H:i') }}</div>
                </td>
                <td>
                    <strong>{{ $r->morador_nome ?: '—' }}</strong>
                    <div class="muted">{{ $r->condominio?->nome }}</div>
                </td>
                <td>{{ trim(($r->bloco ? 'Bloco '.$r->bloco.' · ' : '').($r->apartamento ? 'Apt '.$r->apartamento : '')) ?: '—' }}</td>
                <td>
                    {{ $r->tablet_nome ?: '—' }}
                    <div class="muted">Porta {{ $r->porta ?: '—' }}</div>
                </td>
                <td onclick="event.stopPropagation()">
                    @if($r->senha)
                        <div class="pwd-wrap">
                            <code class="pwd" data-pwd="{{ $r->senha }}">••••••</code>
                            <button type="button" class="icon-mini" data-reveal title="Mostrar">◎</button>
                            <button type="button" class="icon-mini" data-copy="{{ $r->senha }}" title="Copiar">⎘</button>
                        </div>
                    @else
                        <span class="muted">—</span>
                    @endif
                </td>
                <td class="{{ $atrasada ? 'tone-danger' : 'muted' }}">{{ $espera }}</td>
                <td><span class="badge {{ $badge($r->status) }}">{{ $r->status }}</span></td>
                <td onclick="event.stopPropagation()"><a class="btn ghost" href="{{ route('correspondencias.show', $r) }}">Ver</a></td>
            </tr>
        @empty
            <tr>
                <td colspan="8">
                    <div class="empty">
                        <b>Nenhuma correspondência neste filtro</b>
                        <p class="muted">Ajuste o recorte ou sincronize a API Exclusive Locker.</p>
                        <a class="btn" href="{{ route('integracao.index') }}">Ir para integração</a>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
    <div class="table-foot">
        <span class="muted">Mostrando {{ $rows->firstItem() ?: 0 }}–{{ $rows->lastItem() ?: 0 }} de {{ number_format($rows->total(), 0, ',', '.') }}</span>
        {{ $rows->links() }}
    </div>
</div>
@endsection
@section('scripts')
<script>
document.querySelectorAll('[data-reveal]').forEach(function (btn) {
  btn.addEventListener('click', function (e) {
    e.preventDefault();
    var code = btn.parentElement.querySelector('.pwd');
    if (!code) return;
    var open = code.classList.toggle('is-open');
    code.textContent = open ? code.getAttribute('data-pwd') : '••••••';
  });
});
document.querySelectorAll('[data-copy]').forEach(function (btn) {
  btn.addEventListener('click', function (e) {
    e.preventDefault();
    navigator.clipboard.writeText(btn.getAttribute('data-copy') || '').then(function () {
      btn.textContent = 'ok';
      setTimeout(function () { btn.textContent = '⎘'; }, 900);
    });
  });
});
</script>
@endsection
