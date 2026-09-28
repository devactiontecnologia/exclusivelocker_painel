<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unidade extends Model
{
    protected $fillable = ['condominio_id', 'bloco', 'apartamento', 'identificador', 'status'];

    public function condominio(): BelongsTo
    {
        return $this->belongsTo(Condominio::class);
    }

    public function moradores(): HasMany
    {
        return $this->hasMany(Morador::class);
    }

    public function label(): string
    {
        $parts = array_filter(['Bloco '.$this->bloco, 'Apt '.$this->apartamento]);
        return implode(' · ', $parts) ?: '—';
    }
}
