<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    protected $fillable = [
        'resident_account_id', 'condominio_id', 'assunto', 'mensagem', 'status', 'resposta',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ResidentAccount::class, 'resident_account_id');
    }

    public function condominio(): BelongsTo
    {
        return $this->belongsTo(Condominio::class);
    }
}
