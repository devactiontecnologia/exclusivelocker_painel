@extends('layouts.app')
@section('title', $item->exists ? 'Editar condomínio' : 'Novo condomínio')
@section('content')
@php $edit = $item->exists; @endphp
<header class="page-head">
    <div>
        <p class="eyebrow">Cadastros</p>
        <h1>{{ $edit ? 'Editar condomínio' : 'Cadastrar condomínio' }}</h1>
        <p class="muted">{{ $edit ? 'Atualize identidade, endereço e credenciais da API.' : 'Informe a agregação Exclusive Locker e as credenciais de operação.' }}</p>
    </div>
    <div class="page-head__actions">
        <a class="btn ghost" href="{{ $edit ? route('condominios.show', $item) : route('condominios.index') }}">Cancelar</a>
    </div>
</header>

<form method="post" action="{{ $edit ? route('condominios.update', $item) : route('condominios.store') }}" class="form-shell" autocomplete="off">
    @csrf
    @if($edit) @method('put') @endif

    <section class="card">
        <div class="card-head"><h3>Identidade</h3></div>
        <div class="form-grid">
            <label class="field">
                <span>Nome do condomínio</span>
                <input class="input" name="nome" value="{{ old('nome', $item->nome) }}" required>
            </label>
            <label class="field">
                <span>UUID de agregação</span>
                <input class="input" name="uuid_agregacao" value="{{ old('uuid_agregacao', $item->uuid_agregacao) }}" required>
                <small>Identificador da API de catálogo. Não altere sem validar o sync.</small>
            </label>
            <label class="field">
                <span>CNPJ</span>
                <input class="input" name="cnpj" value="{{ old('cnpj', $item->cnpj) }}" placeholder="00.000.000/0000-00">
            </label>
            <label class="field">
                <span>Status operacional</span>
                <select class="input" name="status">
                    @foreach(['ativo','bloqueado','removido'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $item->status ?: 'ativo') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="card">
        <div class="card-head"><h3>Endereço</h3></div>
        <div class="form-grid">
            <label class="field">
                <span>Cidade</span>
                <input class="input" name="cidade" value="{{ old('cidade', $item->cidade) }}">
            </label>
            <label class="field field--sm">
                <span>UF</span>
                <input class="input" name="uf" value="{{ old('uf', $item->uf) }}" maxlength="2" style="text-transform:uppercase">
            </label>
            <label class="field field--full">
                <span>Endereço</span>
                <input class="input" name="endereco" value="{{ old('endereco', $item->endereco) }}" placeholder="Logradouro, número e complemento">
            </label>
        </div>
    </section>

    <section class="card">
        <div class="card-head">
            <div>
                <h3>Credenciais da API</h3>
                <p class="muted">Usadas no sync de moradores, lockers e correspondências.</p>
            </div>
        </div>
        <div class="form-grid">
            <label class="field">
                <span>E-mail da API</span>
                <input class="input" name="api_email" type="email" value="{{ old('api_email', $item->api_email) }}" autocomplete="off">
            </label>
            <label class="field">
                <span>Senha da API</span>
                <input class="input" name="api_password" type="password" autocomplete="new-password" placeholder="{{ $edit ? 'Deixe em branco para manter a senha atual' : 'Senha de autenticação' }}">
            </label>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn" type="submit">Salvar condomínio</button>
        <a class="btn ghost" href="{{ $edit ? route('condominios.show', $item) : route('condominios.index') }}">Voltar</a>
    </div>
</form>
@endsection
