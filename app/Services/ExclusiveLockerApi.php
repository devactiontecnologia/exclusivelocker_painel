<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExclusiveLockerApi
{
    private ?string $token = null;

    public function authenticate(?string $email = null, ?string $password = null): string
    {
        $email ??= (string) config('locker.email');
        $password ??= (string) config('locker.password');

        $response = Http::timeout(25)
            ->acceptJson()
            ->asJson()
            ->post(rtrim((string) config('locker.identidade'), '/').'/api/identidade/autenticar', [
                'email' => $email,
                'senha' => $password,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Falha na autenticação Exclusive Locker ('.$response->status().').');
        }

        $token = $response->json('accessToken');
        if (! $token) {
            throw new RuntimeException('Token vazio na API de identidade.');
        }

        $this->token = $token;

        return $token;
    }

    public function moradores(string $idAgregacao): array
    {
        $this->ensureToken();
        $url = rtrim((string) config('locker.catalogo'), '/').'/catalogo/moradores/'.$idAgregacao;
        $response = Http::timeout(60)->withToken($this->token)->acceptJson()->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('Falha ao listar moradores ('.$response->status().').');
        }

        return $response->json() ?? [];
    }

    public function movimentos(string $idAgregacao): array
    {
        $this->ensureToken();
        $url = rtrim((string) config('locker.android'), '/').'/android/getdados/'.$idAgregacao;
        $response = Http::timeout(90)->withToken($this->token)->acceptJson()->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('Falha ao listar movimentos ('.$response->status().').');
        }

        return $response->json() ?? [];
    }

    public function ocupacao(string $idAgregacao): array
    {
        $this->ensureToken();
        $url = rtrim((string) config('locker.android'), '/').'/android/getaxaocupacao/'.$idAgregacao;
        $response = Http::timeout(30)->withToken($this->token)->acceptJson()->get($url);
        if (! $response->successful()) {
            return [];
        }

        return $response->json() ?? [];
    }

    private function ensureToken(): void
    {
        if (! $this->token) {
            $this->authenticate();
        }
    }
}
