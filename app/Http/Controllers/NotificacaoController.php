<?php

namespace App\Http\Controllers;

use App\Models\Notificacao;
use App\Support\Auditor;
use Illuminate\Http\Request;

class NotificacaoController extends Controller
{
    public function index()
    {
        return view('notificacoes.index', [
            'rows' => Notificacao::query()->with(['morador', 'condominio'])->latest()->paginate(25),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:160'],
            'mensagem' => ['required', 'string'],
            'canal' => ['required', 'in:push,email,sms'],
        ]);
        $n = Notificacao::query()->create($data + ['status' => 'enviada', 'enviada_em' => now()]);
        Auditor::log('Disparou notificação', 'notificacoes', ['id' => $n->id]);

        return back()->with('ok', 'Notificação registrada para monitoramento.');
    }
}
