<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $fillable = [
        'resident_account_id', 'movimento_id', 'titulo', 'mensagem', 'tipo', 'lida', 'lida_em',
    ];

    protected function casts(): array
    {
        return [
            'lida' => 'boolean',
            'lida_em' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ResidentAccount::class, 'resident_account_id');
    }

    public function movimento(): BelongsTo
    {
        return $this->belongsTo(Movimento::class);
    }
}
