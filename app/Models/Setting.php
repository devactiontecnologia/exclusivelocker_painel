<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['chave', 'valor', 'grupo'];

    public static function get(string $chave, mixed $default = null): mixed
    {
        return Cache::remember("setting.$chave", 60, function () use ($chave, $default) {
            $row = static::query()->where('chave', $chave)->first();
            return $row?->valor ?? $default;
        });
    }

    public static function put(string $chave, mixed $valor, string $grupo = 'geral'): void
    {
        static::query()->updateOrCreate(['chave' => $chave], ['valor' => $valor, 'grupo' => $grupo]);
        Cache::forget("setting.$chave");
    }
}
