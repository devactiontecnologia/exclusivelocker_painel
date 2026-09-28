<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppContent;
use App\Models\AppNotification;
use App\Models\Morador;
use App\Models\Movimento;
use App\Models\ResidentAccount;
use App\Models\SupportTicket;
use App\Support\PhoneMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResidentAuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:180', 'unique:resident_accounts,email'],
            'telefone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'condominio_id' => ['nullable', 'integer', 'exists:condominios,id'],
        ]);

        $morador = $this->findMoradorByPhone($data['telefone'], $data['condominio_id'] ?? null);
        if (! $morador) {
            throw ValidationException::withMessages([
                'telefone' => 'Não encontramos este telefone na base do condomínio. Confirme o número cadastrado na portaria ou aguarde a sincronização.',
            ]);
        }

        if (ResidentAccount::query()->where('morador_id', $morador->id)->exists()) {
            throw ValidationException::withMessages([
                'telefone' => 'Já existe uma conta vinculada a este morador. Faça login ou recupere a senha.',
            ]);
        }

        $account = ResidentAccount::query()->create([
            'morador_id' => $morador->id,
            'condominio_id' => $morador->condominio_id,
            'nome' => $data['nome'] ?: $morador->nome,
            'email' => Str::lower($data['email']),
            'telefone' => PhoneMatcher::normalize($data['telefone']),
            'password' => $data['password'],
            'last_login_at' => now(),
        ]);

        $token = $account->createToken('app-morador')->plainTextToken;

        return response()->json([
            'token' => $token,
            'account' => $this->accountPayload($account->load(['morador.unidade', 'condominio'])),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_token' => ['nullable', 'string'],
            'device_platform' => ['nullable', 'string', 'max:20'],
        ]);

        $account = ResidentAccount::query()->where('email', Str::lower($data['email']))->first();
        if (! $account || ! Hash::check($data['password'], $account->password)) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha incorretos.',
            ]);
        }

        if ($account->morador?->status === 'bloqueado' || $account->morador?->status === 'excluido') {
            throw ValidationException::withMessages([
                'email' => 'Este acesso está bloqueado. Contate a administração.',
            ]);
        }

        $account->forceFill([
            'last_login_at' => now(),
            'device_token' => $data['device_token'] ?? $account->device_token,
            'device_platform' => $data['device_platform'] ?? $account->device_platform,
        ])->save();

        $token = $account->createToken('app-morador')->plainTextToken;

        return response()->json([
            'token' => $token,
            'account' => $this->accountPayload($account->load(['morador.unidade', 'condominio'])),
        ]);
    }

    public function loginByPhone(Request $request)
    {
        $data = $request->validate([
            'telefone' => ['required', 'string', 'max:30'],
            'device_token' => ['nullable', 'string'],
            'device_platform' => ['nullable', 'string', 'max:20'],
        ]);

        $morador = $this->findMoradorByPhone($data['telefone'], null);
        if (! $morador) {
            throw ValidationException::withMessages([
                'telefone' => 'Não encontramos este telefone na base do condomínio.',
            ]);
        }

        if (in_array($morador->status, ['bloqueado', 'excluido'], true)) {
            throw ValidationException::withMessages([
                'telefone' => 'Este acesso está bloqueado. Contate a administração.',
            ]);
        }

        $phone = PhoneMatcher::normalize($data['telefone']);
        $account = ResidentAccount::query()->where('morador_id', $morador->id)->first();

        if (! $account) {
            $account = ResidentAccount::query()->create([
                'morador_id' => $morador->id,
                'condominio_id' => $morador->condominio_id,
                'nome' => $morador->nome,
                'email' => 'tel.'.$phone.'@app.exclusivelocker.local',
                'telefone' => $phone,
                'password' => Str::random(32),
                'last_login_at' => now(),
            ]);
        } else {
            $account->forceFill([
                'telefone' => $phone ?: $account->telefone,
                'last_login_at' => now(),
                'device_token' => $data['device_token'] ?? $account->device_token,
                'device_platform' => $data['device_platform'] ?? $account->device_platform,
            ])->save();
        }

        $token = $account->createToken('app-morador')->plainTextToken;

        return response()->json([
            'token' => $token,
            'account' => $this->accountPayload($account->load(['morador.unidade', 'condominio'])),
        ]);
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $account = ResidentAccount::query()->where('email', Str::lower($request->email))->first();

        if ($account) {
            $token = Str::random(64);
            DB::table('password_reset_tokens_residents')->updateOrInsert(
                ['email' => $account->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );
            // Em produção: enviar e-mail. Localmente o token vai no log.
            logger()->info('Resident password reset', ['email' => $account->email, 'token' => $token]);
        }

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, enviaremos as instruções de redefinição.',
            // Apenas em local/debug para facilitar testes sem SMTP.
            'debug_token' => (app()->environment('local') && isset($token)) ? $token : null,
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $row = DB::table('password_reset_tokens_residents')->where('email', Str::lower($data['email']))->first();
        if (! $row || ! Hash::check($data['token'], $row->token)) {
            throw ValidationException::withMessages(['email' => 'Link inválido ou expirado.']);
        }

        $account = ResidentAccount::query()->where('email', Str::lower($data['email']))->firstOrFail();
        $account->update(['password' => $data['password']]);
        DB::table('password_reset_tokens_residents')->where('email', $account->email)->delete();
        $account->tokens()->delete();

        return response()->json(['message' => 'Senha atualizada. Faça login novamente.']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'account' => $this->accountPayload($request->user()->load(['morador.unidade', 'condominio'])),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    public function lookupPhone(Request $request)
    {
        $data = $request->validate([
            'telefone' => ['required', 'string'],
            'condominio_id' => ['nullable', 'integer'],
        ]);
        $morador = $this->findMoradorByPhone($data['telefone'], $data['condominio_id'] ?? null);

        if (! $morador) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'nome' => $morador->nome,
            'unidade' => $morador->unidade?->label(),
            'condominio' => $morador->condominio?->nome,
            'condominio_id' => $morador->condominio_id,
            'has_account' => ResidentAccount::query()->where('morador_id', $morador->id)->exists(),
        ]);
    }

    private function findMoradorByPhone(string $telefone, ?int $condoId): ?Morador
    {
        $q = Morador::query()->with(['unidade', 'condominio'])->where('status', 'ativo');
        if ($condoId) {
            $q->where('condominio_id', $condoId);
        }

        foreach ($q->get() as $morador) {
            if (PhoneMatcher::matches($telefone, $morador->telefone)) {
                return $morador;
            }
        }

        return null;
    }

    public function accountPayload(ResidentAccount $account): array
    {
        $m = $account->morador;

        return [
            'id' => $account->id,
            'nome' => $account->nome,
            'email' => $account->email,
            'telefone' => $account->telefone,
            'notify_push' => $account->notify_push,
            'notify_email' => $account->notify_email,
            'condominio' => [
                'id' => $account->condominio_id,
                'nome' => $account->condominio?->nome,
            ],
            'unidade' => $m?->unidade?->label(),
            'bloco' => $m?->unidade?->bloco,
            'apartamento' => $m?->unidade?->apartamento,
            'morador_id' => $account->morador_id,
            'morador_uuid' => $m?->uuid_externo,
            'status_morador' => $m?->status,
        ];
    }
}
