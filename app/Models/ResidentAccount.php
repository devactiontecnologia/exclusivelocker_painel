<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ResidentAccount extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'morador_id', 'condominio_id', 'nome', 'email', 'telefone', 'password',
        'notify_push', 'notify_email', 'device_token', 'device_platform',
        'email_verified_at', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token', 'device_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'notify_push' => 'boolean',
            'notify_email' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function morador(): BelongsTo
    {
        return $this->belongsTo(Morador::class);
    }

    public function condominio(): BelongsTo
    {
        return $this->belongsTo(Condominio::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}
