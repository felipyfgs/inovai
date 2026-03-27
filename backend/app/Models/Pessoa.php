<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pessoa extends Model
{
    protected $table = 'pessoas';

    protected $fillable = [
        'company_id',
        'tipo',
        'tipo_pessoa',
        'razao_social',
        'fantasia',
        'cpf_cnpj',
        'ie',
        'im',
        'ind_ie',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'municipio',
        'municipio_ibge',
        'uf',
        'cep',
        'pais',
        'pais_ibge',
        'telefone',
        'celular',
        'email',
        'observacoes',
        'condicao_pagamento',
        'prazo_entrega',
        'avaliacao',
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
}
