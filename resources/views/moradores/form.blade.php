@extends('layouts.app')
@section('title', 'Editar morador')
@section('content')
<header class="page-head">
    <div>
        <p class="eyebrow">Cadastros</p>
        <h1>Editar morador</h1>
        <p class="muted">{{ $item->condominio?->nome }} · {{ $item->unidade?->label() ?: 'sem unidade' }}</p>
    </div>
    <div class="page-head__actions">
        <a class="btn ghost" href="{{ route('moradores.show', $item) }}">Cancelar</a>
    </div>
</header>

<form method="post" action="{{ route('moradores.update', $item) }}" class="form-shell" autocomplete="off">
    @csrf @method('put')
    <section class="card">
        <div class="card-head"><h3>Dados cadastrais</h3></div>
        <div class="form-grid">
            <label class="field field--full">
                <span>Nome</span>
                <input class="input" name="nome" value="{{ old('nome', $item->nome) }}" required>
            </label>
            <label class="field">
                <span>Telefone</span>
                <input class="input" name="telefone" value="{{ old('telefone', $item->telefone) }}">
            </label>
            <label class="field field--sm">
                <span>DDI</span>
                <input class="input" name="tel_ddi" value="{{ old('tel_ddi', $item->tel_ddi) }}" maxlength="8">
            </label>
            <label class="field">
                <span>Status</span>
                <select class="input" name="status">
                    @foreach(['ativo','bloqueado','excluido'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $item->status) === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Unidade (somente leitura)</span>
                <input class="input" value="{{ $item->unidade?->label() ?: '—' }}" disabled>
            </label>
        </div>
        <p class="muted" style="margin-top:12px">Condomínio e unidade vêm da API. Alterações locais valem para nome, telefone e status.</p>
    </section>
    <div class="form-actions">
        <button class="btn" type="submit">Salvar alterações</button>
        <a class="btn ghost" href="{{ route('moradores.show', $item) }}">Voltar</a>
    </div>
</form>
@endsection
