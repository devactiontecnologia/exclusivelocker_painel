<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    protected $fillable = ['tipo', 'severidade', 'titulo', 'mensagem', 'lido', 'resolvido_em'];

    protected function casts(): array
    {
        return [
            'lido' => 'boolean',
            'resolvido_em' => 'datetime',
        ];
    }
}
