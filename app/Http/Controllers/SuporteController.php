<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SuporteController extends Controller
{
    public function index(Request $request)
    {
        $q = SupportTicket::query()
            ->with(['account', 'condominio'])
            ->latest();

        if ($status = $request->string('status')->toString()) {
            $q->where('status', $status);
        }

        return view('suporte.index', [
            'rows' => $q->paginate(30)->withQueryString(),
            'status' => $status,
            'abertos' => SupportTicket::query()->whereIn('status', ['aberto', 'em_andamento'])->count(),
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['account', 'condominio']);

        return view('suporte.show', ['ticket' => $ticket]);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'status' => 'required|in:aberto,em_andamento,resolvido',
            'resposta' => 'nullable|string|max:5000',
        ]);

        $ticket->update($data);
        Cache::forget('sidebar.meta');

        return back()->with('ok', 'Ticket atualizado.');
    }
}
