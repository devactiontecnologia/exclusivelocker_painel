<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Locker extends Model
{
    protected $fillable = [
        'condominio_id', 'tablet_id', 'nome', 'ocupados', 'livres', 'total',
        'taxa_ocupacao', 'status', 'atualizado_em',
    ];

    protected function casts(): array
    {
        return ['atualizado_em' => 'datetime'];
    }

    public function condominio(): BelongsTo
    {
        return $this->belongsTo(Condominio::class);
    }

    public function movimentos(): HasMany
    {
        return $this->hasMany(Movimento::class);
    }

    public function occupancyPct(): float
    {
        $raw = $this->taxa_ocupacao;
        if (is_numeric($raw)) {
            return min(100.0, max(0.0, (float) $raw));
        }
        if ((int) $this->total > 0) {
            return round(((int) $this->ocupados / (int) $this->total) * 100, 1);
        }

        return 0.0;
    }

    public function isStale(): bool
    {
        return ! $this->atualizado_em || $this->atualizado_em->lt(now()->subMinutes(15));
    }

    public function isOnline(): bool
    {
        return $this->status === 'online' && ! $this->isStale();
    }
}
