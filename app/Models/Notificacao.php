<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacao extends Model
{
    protected $table = 'notificacoes';

    protected $fillable = [
        'condominio_id', 'morador_id', 'canal', 'titulo', 'mensagem', 'status', 'enviada_em', 'erro',
    ];

    protected function casts(): array
    {
        return ['enviada_em' => 'datetime'];
    }

    public function condominio(): BelongsTo
    {
        return $this->belongsTo(Condominio::class);
    }

    public function morador(): BelongsTo
    {
        return $this->belongsTo(Morador::class);
    }
}
