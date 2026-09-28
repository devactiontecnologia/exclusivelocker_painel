<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimento extends Model
{
    protected $fillable = [
        'condominio_id', 'morador_id', 'locker_id', 'id_externo', 'guid',
        'morador_nome', 'bloco', 'apartamento', 'porta', 'senha', 'codigo',
        'entregador', 'data_entrada', 'data_retirada', 'status', 'tablet_nome', 'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'data_entrada' => 'datetime',
            'data_retirada' => 'datetime',
        ];
    }

    public function condominio(): BelongsTo
    {
        return $this->belongsTo(Condominio::class);
    }

    public function morador(): BelongsTo
    {
        return $this->belongsTo(Morador::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(Locker::class);
    }

    public function isPendente(): bool
    {
        return $this->status === 'pendente';
    }
}
