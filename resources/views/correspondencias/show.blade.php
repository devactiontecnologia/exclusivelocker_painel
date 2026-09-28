@extends('layouts.app')
@section('title', $movimento->morador_nome ?: 'Correspondência')
@section('content')
@php
    $badge = $movimento->status === 'retirada' ? 'b-ok' : ($movimento->status === 'pendente' ? 'b-warn' : 'b-danger');
    $unidade = trim(($movimento->bloco ? 'Bloco '.$movimento->bloco.' · ' : '').($movimento->apartamento ? 'Apt '.$movimento->apartamento : ''));
    $steps = [
        ['label' => 'Recebida', 'done' => (bool) $movimento->data_entrada, 'at' => optional($movimento->data_entrada)->format('d/m H:i')],
        ['label' => 'Aguardando', 'done' => $movimento->status === 'pendente', 'at' => $movimento->status === 'pendente' ? 'agora' : null],
        ['label' => 'Retirada', 'done' => (bool) $movimento->data_retirada, 'at' => optional($movimento->data_retirada)->format('d/m H:i')],
    ];
    if ($movimento->status === 'expirada') {
        $steps[1] = ['label' => 'Expirada', 'done' => true, 'at' => 'prazo encerrado'];
        $steps[2]['done'] = false;
    }
@endphp
<header class="page-head">
    <div>
        <p class="eyebrow">Detalhe operacional</p>
        <h1>{{ $movimento->morador_nome ?: 'Correspondência' }}</h1>
        <p class="muted">{{ $movimento->condominio?->nome ?: 'Condomínio' }} · {{ $unidade ?: 'sem unidade' }} · Porta {{ $movimento->porta ?: '—' }}</p>
    </div>
    <div class="page-head__actions">
        <a class="btn ghost" href="{{ url()->previous(route('correspondencias.index')) }}">Voltar</a>
        @if($movimento->morador_id)
            <a class="btn ghost" href="{{ route('moradores.show', $movimento->morador_id) }}">Ficha do morador</a>
        @endif
        <button class="btn ghost" type="button" onclick="window.print()">Imprimir</button>
        <span class="badge {{ $badge }}" style="padding:10px 14px">{{ $movimento->status }}</span>
    </div>
</header>

@if($late)
    <div class="errors">Esta encomenda está há mais de 24h no locker. Oriente a retirada ou o aviso ao morador.</div>
@endif

<div class="flow-steps">
    @foreach($steps as $step)
        <article class="{{ $step['done'] ? 'is-done' : '' }}">
            <b>{{ $step['label'] }}</b>
            <span>{{ $step['at'] ?: '—' }}</span>
        </article>
    @endforeach
</div>

<div class="grid detail-grid">
    <section class="card pwd-card">
        <p class="eyebrow">Senha de retirada</p>
        <code class="pwd-xl" id="pwd-full">{{ $movimento->senha ?: '—' }}</code>
        <p class="muted">Porta {{ $movimento->porta ?: '—' }} · {{ $duration }}</p>
        <div class="actions" style="margin-top:14px">
            @if($movimento->senha)
                <button class="btn" type="button" data-copy="{{ $movimento->senha }}" data-copy-label="Copiar senha">Copiar senha</button>
            @endif
            @if($movimento->codigo)
                <button class="btn ghost" type="button" data-copy="{{ $movimento->codigo }}" data-copy-label="Copiar código">Copiar código</button>
            @endif
            @if($whatsapp)
                <a class="btn ghost" href="{{ $whatsapp }}" target="_blank" rel="noopener">WhatsApp</a>
            @endif
            <form method="post" action="{{ route('correspondencias.notify', $movimento) }}">
                @csrf
                <button class="btn ghost" type="submit">Registrar aviso</button>
            </form>
        </div>
    </section>

    <section class="card">
        <div class="card-head"><h3>Identificação</h3></div>
        <dl class="meta-grid">
            <div><span>Morador</span><b>{{ $movimento->morador_nome ?: '—' }}</b></div>
            <div><span>Telefone</span><b>{{ $movimento->morador?->telefone ?: '—' }}</b></div>
            <div><span>Unidade</span><b>{{ $unidade ?: '—' }}</b></div>
            <div><span>Locker</span><b>{{ $movimento->tablet_nome ?: '—' }}</b></div>
            <div><span>Porta</span><b>{{ $movimento->porta ?: '—' }}</b></div>
            <div><span>Entregador</span><b>{{ $movimento->entregador ?: '—' }}</b></div>
            <div><span>Código</span><b>{{ $movimento->codigo ?: '—' }}</b></div>
            <div><span>ID externo</span><b>{{ $movimento->id_externo ?: '—' }}</b></div>
        </dl>
    </section>

    <section class="card">
        <div class="card-head"><h3>Prazos</h3></div>
        <dl class="meta-grid">
            <div><span>Entrada</span><b>{{ optional($movimento->data_entrada)->format('d/m/Y H:i') ?: '—' }}</b></div>
            <div><span>Retirada</span><b>{{ optional($movimento->data_retirada)->format('d/m/Y H:i') ?: 'Aguardando' }}</b></div>
            <div><span>Tempo</span><b class="{{ $late ? 'tone-danger' : '' }}">{{ $duration }}</b></div>
            <div><span>Condomínio</span><b>{{ $movimento->condominio?->nome ?: '—' }}</b></div>
        </dl>
        <div class="actions" style="margin-top:16px">
            <a class="btn ghost" href="{{ route('correspondencias.index', ['q' => $movimento->morador_nome, 'status' => 'pendente']) }}">Pendências do morador</a>
            <a class="btn ghost" href="{{ route('historico.index', ['q' => $movimento->morador_nome]) }}">Histórico</a>
        </div>
    </section>
</div>

@if($related->isNotEmpty())
<section class="card" style="margin-top:16px">
    <div class="card-head">
        <h3>Mesma unidade</h3>
        <span class="muted">Últimas correspondências de {{ $unidade ?: 'esta unidade' }}</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Entrada</th><th>Porta</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($related as $item)
                <tr>
                    <td>{{ optional($item->data_entrada)->format('d/m/Y H:i') }}</td>
                    <td>{{ $item->porta ?: '—' }}</td>
                    <td><span class="badge {{ $item->status === 'retirada' ? 'b-ok' : ($item->status === 'pendente' ? 'b-warn' : 'b-danger') }}">{{ $item->status }}</span></td>
                    <td><a class="btn ghost" href="{{ route('correspondencias.show', $item) }}">Abrir</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif

@if($movimento->payload)
<details class="card tech-fold" style="margin-top:16px">
    <summary>
        <h3>Dados técnicos da API</h3>
        <span class="muted">GUID {{ $movimento->guid ?: '—' }} · clique para expandir</span>
    </summary>
    <pre class="payload">{{ json_encode($movimento->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</details>
@endif
@endsection
@section('scripts')
<script>
document.querySelectorAll('[data-copy]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var label = btn.getAttribute('data-copy-label') || 'Copiar';
    navigator.clipboard.writeText(btn.getAttribute('data-copy') || '').then(function () {
      btn.textContent = 'Copiado';
      setTimeout(function () { btn.textContent = label; }, 1200);
    });
  });
});
</script>
@endsection
