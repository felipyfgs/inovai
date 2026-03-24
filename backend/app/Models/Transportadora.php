<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transportadora extends Model
{
    protected $fillable = [
        'company_id',
        'razao_social',
        'fantasia',
        'cnpj',
        'ie',
        'rntrc',
        'logradouro',
        'numero',
        'bairro',
        'municipio',
        'municipio_ibge',
        'uf',
        'cep',
        'telefone',
        'email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class);
    }
}
