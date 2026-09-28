<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppContent extends Model
{
    protected $fillable = ['chave', 'titulo', 'corpo'];

    public static function getByKey(string $chave): ?self
    {
        return static::query()->where('chave', $chave)->first();
    }
}
