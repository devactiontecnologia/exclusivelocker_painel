<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Morador extends Model
{
    protected $table = 'moradores';

    public function getTable()
    {
        return 'moradores';
    }
    protected $fillable = [
        'condominio_id', 'unidade_id', 'uuid_externo', 'nome', 'telefone',
        'tel_ddi', 'status', 'quantidade_encomendas', 'data_cadastro_api', 'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'data_cadastro_api' => 'datetime',
        ];
    }

    public function condominio(): BelongsTo
    {
        return $this->belongsTo(Condominio::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function movimentos(): HasMany
    {
        return $this->hasMany(Movimento::class);
    }
}
