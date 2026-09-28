<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $fillable = ['level', 'canal', 'mensagem', 'contexto'];

    protected function casts(): array
    {
        return ['contexto' => 'array'];
    }
}
