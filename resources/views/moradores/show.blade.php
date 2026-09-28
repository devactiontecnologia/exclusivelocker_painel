@extends('layouts.app')
@section('title', $morador->nome)
@section('content')
@php $badge = $morador->status === 'ativo' ? 'b-ok' : ($morador->status === 'excluido' ? 'b-mute' : 'b-warn'); @endphp
<header class="page-head">
    <div>
        <p class="eyebrow">Ficha do morador</p>
        <h1>{{ $morador->nome }}</h1>
        <p class="muted">{{ $morador->condominio?->nome }} · {{ $morador->unidade?->label() ?: 'sem unidade' }} · {{ $morador->telefone ?: 'sem telefone' }}</p>
    </div>
    <div class="page-head__actions">
        <a class="btn ghost" href="{{ route('moradores.index', ['condominio_id' => $morador->condominio_id]) }}">Lista</a>
        <a class="btn ghost" href="{{ route('moradores.edit', $morador) }}">Editar</a>
        @if($whatsapp)
            <a class="btn ghost" href="{{ $whatsapp }}" target="_blank" rel="noopener">WhatsApp</a>
        @endif
        <form method="post" action="{{ route('moradores.toggle', $morador) }}">@csrf
            <button class="btn ghost">{{ $morador->status === 'ativo' ? 'Bloquear' : 'Reativar' }}</button>
        </form>
        <span class="badge {{ $badge }}" style="padding:10px 14px">{{ $morador->status }}</span>
    </div>
</header>

<div class="grid kpis dash-kpis">
    <article class="card kpi-card"><div class="kpi-card__top"><span>Correspondências</span></div><div class="value">{{ $kpis['total'] }}</div><div class="hint">histórico vinculado</div></article>
    <a class="card kpi-card" href="{{ route('correspondencias.index', ['q' => $morador->nome, 'status' => 'pendente']) }}"><div class="kpi-card__top"><span>Pendências</span></div><div class="value tone-warn">{{ $kpis['pendentes'] }}</div><div class="hint">aguardando retirada</div></a>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Última entrada</span></div><div class="value" style="font-size:22px">{{ $kpis['ultima'] ? \Illuminate\Support\Carbon::parse($kpis['ultima'])->format('d/m H:i') : '—' }}</div><div class="hint">movimento mais recente</div></article>
    <article class="card kpi-card"><div class="kpi-card__top"><span>Encomendas API</span></div><div class="value tone-gold">{{ $morador->quantidade_encomendas }}</div><div class="hint">contador sincronizado</div></article>
</div>

<div class="grid dash-main">
    <section class="card">
        <div class="card-head"><h3>Identificação</h3></div>
        <dl class="meta-grid">
            <div><span>Condomínio</span><b>{{ $morador->condominio?->nome ?: '—' }}</b></div>
            <div><span>Unidade</span><b>{{ $morador->unidade?->label() ?: '—' }}</b></div>
            <div><span>Telefone</span><b>{{ $morador->telefone ?: '—' }}</b></div>
            <div><span>DDI</span><b>{{ $morador->tel_ddi ?: '—' }}</b></div>
            <div><span>UUID externo</span><b class="mono-id">{{ $morador->uuid_externo ?: '—' }}</b></div>
            <div><span>Cadastro API</span><b>{{ optional($morador->data_cadastro_api)->format('d/m/Y H:i') ?: '—' }}</b></div>
        </dl>
    </section>
    <section class="card">
        <div class="card-head"><h3>Atalhos</h3></div>
        <div class="stack">
            <a class="btn ghost" href="{{ route('correspondencias.index', ['q' => $morador->nome]) }}">Todas as correspondências</a>
            <a class="btn ghost" href="{{ route('historico.index', ['q' => $morador->nome]) }}">Histórico do morador</a>
            @if($morador->condominio_id)
                <a class="btn ghost" href="{{ route('condominios.show', $morador->condominio_id) }}">Ficha do condomínio</a>
            @endif
        </div>
    </section>
</div>

<section class="card" style="margin-top:16px">
    <div class="card-head">
        <div>
            <h3>Movimentações</h3>
            <p class="muted">Entradas encontradas pelo vínculo ou pelo nome no condomínio</p>
        </div>
    </div>
    <div class="table-wrap mail-table">
        <table>
            <thead><tr><th>Entrada</th><th>Locker / porta</th><th>Senha</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($hist as $m)
                <tr onclick="window.location='{{ route('correspondencias.show', $m) }}'">
                    <td>{{ optional($m->data_entrada)->format('d/m/Y H:i') }}</td>
                    <td>{{ $m->tablet_nome }} · {{ $m->porta ?: '—' }}</td>
                    <td onclick="event.stopPropagation()">
                        @if($m->senha)
                            <div class="pwd-wrap">
                                <code class="pwd" data-pwd="{{ $m->senha }}">••••••</code>
                                <button type="button" class="icon-mini" data-copy="{{ $m->senha }}" title="Copiar">⎘</button>
                            </div>
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="badge {{ $m->status === 'retirada' ? 'b-ok' : ($m->status === 'pendente' ? 'b-warn' : 'b-danger') }}">{{ $m->status }}</span></td>
                    <td onclick="event.stopPropagation()"><a class="btn ghost" href="{{ route('correspondencias.show', $m) }}">Ver</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty">
                            <b>Nenhuma correspondência encontrada</b>
                            <p class="muted">Este morador ainda não possui movimentos sincronizados neste condomínio.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-foot">{{ $hist->links() }}</div>
    @if($morador->status !== 'excluido')
        <form method="post" action="{{ route('moradores.destroy', $morador) }}" onsubmit="return confirm('Marcar este morador como excluído?')" style="margin-top:14px">
            @csrf @method('delete')
            <button class="btn danger">Excluir cadastro</button>
        </form>
    @endif
</section>
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
