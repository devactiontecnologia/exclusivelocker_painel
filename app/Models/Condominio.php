<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Condominio extends Model
{
    protected $fillable = [
        'uuid_agregacao', 'nome', 'cnpj', 'cidade', 'uf', 'endereco',
        'api_email', 'api_password', 'status', 'ultima_sincronizacao',
    ];

    protected $hidden = ['api_password'];

    protected function casts(): array
    {
        return ['ultima_sincronizacao' => 'datetime'];
    }

    public function unidades(): HasMany
    {
        return $this->hasMany(Unidade::class);
    }

    public function moradores(): HasMany
    {
        return $this->hasMany(Morador::class);
    }

    public function movimentos(): HasMany
    {
        return $this->hasMany(Movimento::class);
    }

    public function lockers(): HasMany
    {
        return $this->hasMany(Locker::class);
    }
}
