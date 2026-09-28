<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ResidentAuthController;
use App\Models\AppContent;
use App\Models\AppNotification;
use App\Models\Movimento;
use App\Models\SupportTicket;
use App\Services\LockerSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResidentAppController extends Controller
{
    public function dashboard(Request $request)
    {
        $account = $request->user()->load(['morador.unidade', 'condominio']);
        $movimentos = $this->scopedMovimentos($account)->get();
        $pendentes = $movimentos->where('status', 'pendente')->values();
        $retiradas = $movimentos->where('status', 'retirada')->values();

        return response()->json([
            'account' => app(ResidentAuthController::class)->accountPayload($account),
            'resumo' => [
                'pendentes' => $pendentes->count(),
                'retiradas' => $retiradas->count(),
                'total' => $movimentos->count(),
                'nao_lidas' => AppNotification::query()
                    ->where('resident_account_id', $account->id)
                    ->where('lida', false)
                    ->count(),
            ],
            'ultimas_entregas' => $pendentes->sortByDesc('data_entrada')->take(5)->values()->map(fn ($m) => $this->movimentoPayload($m)),
            'ultimas_retiradas' => $retiradas->sortByDesc('data_retirada')->take(5)->values()->map(fn ($m) => $this->movimentoPayload($m)),
            'sync_at' => optional($account->condominio?->ultima_sincronizacao)?->toIso8601String(),
        ]);
    }

    public function pendentes(Request $request)
    {
        $rows = $this->scopedMovimentos($request->user())
            ->where('status', 'pendente')
            ->orderByDesc('data_entrada')
            ->get()
            ->map(fn ($m) => $this->movimentoPayload($m));

        return response()->json(['data' => $rows]);
    }

    public function historico(Request $request)
    {
        $rows = $this->scopedMovimentos($request->user())
            ->whereIn('status', ['retirada', 'expirada'])
            ->orderByDesc('data_retirada')
            ->orderByDesc('data_entrada')
            ->paginate(30);

        return response()->json([
            'data' => collect($rows->items())->map(fn ($m) => $this->movimentoPayload($m)),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function showMovimento(Request $request, int $id)
    {
        $m = $this->scopedMovimentos($request->user())->where('id', $id)->firstOrFail();

        return response()->json(['data' => $this->movimentoPayload($m, true)]);
    }

    public function refresh(Request $request, LockerSyncService $sync)
    {
        $account = $request->user()->load('condominio');
        if ($account->condominio) {
            try {
                $sync->syncCondominio($account->condominio);
            } catch (\Throwable $e) {
                return response()->json([
                    'message' => 'Não foi possível atualizar agora.',
                    'error' => $e->getMessage(),
                ], 502);
            }
        }

        return $this->dashboard($request);
    }

    public function updateProfile(Request $request)
    {
        $account = $request->user();
        $data = $request->validate([
            'nome' => ['sometimes', 'string', 'max:180'],
            'telefone' => ['sometimes', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'password_atual' => ['required_with:password', 'string'],
        ]);

        if (! empty($data['password'])) {
            if (! Hash::check($data['password_atual'], $account->password)) {
                return response()->json(['message' => 'Senha atual incorreta.'], 422);
            }
            $account->password = $data['password'];
        }

        if (isset($data['nome'])) {
            $account->nome = $data['nome'];
        }
        if (isset($data['telefone'])) {
            $account->telefone = preg_replace('/\D+/', '', $data['telefone']);
        }
        $account->save();

        return response()->json([
            'account' => app(ResidentAuthController::class)->accountPayload($account->fresh()->load(['morador.unidade', 'condominio'])),
            'message' => 'Perfil atualizado.',
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $data = $request->validate([
            'notify_push' => ['sometimes', 'boolean'],
            'notify_email' => ['sometimes', 'boolean'],
            'device_token' => ['nullable', 'string'],
            'device_platform' => ['nullable', 'string', 'max:20'],
        ]);

        $account = $request->user();
        $account->fill($data)->save();

        return response()->json([
            'notify_push' => $account->notify_push,
            'notify_email' => $account->notify_email,
            'message' => 'Preferências salvas.',
        ]);
    }

    public function notifications(Request $request)
    {
        $rows = AppNotification::query()
            ->where('resident_account_id', $request->user()->id)
            ->latest()
            ->paginate(40);

        return response()->json([
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'total' => $rows->total(),
                'nao_lidas' => AppNotification::query()
                    ->where('resident_account_id', $request->user()->id)
                    ->where('lida', false)
                    ->count(),
            ],
        ]);
    }

    public function readNotification(Request $request, AppNotification $notification)
    {
        abort_unless($notification->resident_account_id === $request->user()->id, 403);
        $notification->update(['lida' => true, 'lida_em' => now()]);

        return response()->json(['ok' => true]);
    }

    public function readAllNotifications(Request $request)
    {
        AppNotification::query()
            ->where('resident_account_id', $request->user()->id)
            ->where('lida', false)
            ->update(['lida' => true, 'lida_em' => now()]);

        return response()->json(['ok' => true]);
    }

    public function support(Request $request)
    {
        $data = $request->validate([
            'assunto' => ['required', 'string', 'max:160'],
            'mensagem' => ['required', 'string', 'max:4000'],
        ]);

        $ticket = SupportTicket::query()->create([
            'resident_account_id' => $request->user()->id,
            'condominio_id' => $request->user()->condominio_id,
            'assunto' => $data['assunto'],
            'mensagem' => $data['mensagem'],
        ]);

        return response()->json(['data' => $ticket, 'message' => 'Solicitação enviada à administração.'], 201);
    }

    public function myTickets(Request $request)
    {
        $rows = SupportTicket::query()
            ->where('resident_account_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function content(string $chave)
    {
        $row = AppContent::getByKey($chave);
        abort_unless($row, 404);

        return response()->json(['data' => $row]);
    }

    private function scopedMovimentos($account)
    {
        $morador = $account->morador;

        return Movimento::query()->where(function ($w) use ($account, $morador) {
            $w->where('morador_id', $account->morador_id);
            if ($morador?->nome) {
                $w->orWhere(function ($q) use ($account, $morador) {
                    $q->where('condominio_id', $account->condominio_id)
                        ->where('morador_nome', $morador->nome);
                });
            }
            if ($morador?->uuid_externo) {
                // fallback já coberto por morador_id quando sync vincula
            }
        });
    }

    private function movimentoPayload(Movimento $m, bool $detail = false): array
    {
        $payload = [
            'id' => $m->id,
            'status' => $m->status,
            'morador_nome' => $m->morador_nome,
            'bloco' => $m->bloco,
            'apartamento' => $m->apartamento,
            'unidade' => trim(($m->bloco ? 'Bloco '.$m->bloco.' · ' : '').($m->apartamento ? 'Apt '.$m->apartamento : '')) ?: null,
            'porta' => $m->porta,
            'senha' => $m->senha,
            'codigo' => $m->codigo,
            'locker' => $m->tablet_nome,
            'entregador' => $m->entregador,
            'data_entrada' => optional($m->data_entrada)?->toIso8601String(),
            'data_retirada' => optional($m->data_retirada)?->toIso8601String(),
            'prazo_horas' => $m->data_entrada && $m->status === 'pendente'
                ? max(0, 72 - (int) $m->data_entrada->diffInHours(now()))
                : null,
        ];

        if ($detail) {
            $payload['guid'] = $m->guid;
            $payload['id_externo'] = $m->id_externo;
        }

        return $payload;
    }
}
