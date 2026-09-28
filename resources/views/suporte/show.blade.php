@extends('layouts.app')
@section('title','Ticket #'.$ticket->id)
@section('content')
<p><a href="{{ route('suporte.index') }}">&larr; Voltar</a></p>
<h1>{{ $ticket->assunto }}</h1>
<p class="muted">
    {{ $ticket->account?->nome }} · {{ $ticket->account?->email }} · {{ $ticket->account?->telefone }}
    · {{ $ticket->condominio?->nome }}
    · {{ $ticket->created_at?->format('d/m/Y H:i') }}
</p>

@if(session('ok'))
    <div class="alert ok" style="margin:12px 0">{{ session('ok') }}</div>
@endif

<div class="card" style="margin-top:16px;padding:18px">
    <h3>Mensagem do morador</h3>
    <p style="white-space:pre-wrap;margin-top:8px">{{ $ticket->mensagem }}</p>
</div>

<form method="post" action="{{ route('suporte.update',$ticket) }}" class="card" style="margin-top:16px;padding:18px;display:grid;gap:12px;max-width:640px">
    @csrf
    @method('PUT')
    <label>
        <span>Status</span>
        <select name="status" required>
            @foreach(['aberto','em_andamento','resolvido'] as $s)
                <option value="{{ $s }}" @selected($ticket->status===$s)>{{ str_replace('_',' ',$s) }}</option>
            @endforeach
        </select>
    </label>
    <label>
        <span>Resposta da administração</span>
        <textarea name="resposta" rows="6" placeholder="Opcional — registrado no painel">{{ old('resposta', $ticket->resposta) }}</textarea>
    </label>
    <button class="btn" type="submit">Salvar</button>
</form>
@endsection
