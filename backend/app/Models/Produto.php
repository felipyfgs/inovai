<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Produto extends Model
{
    protected $fillable = [
        'company_id',
        'codigo',
        'codigo_barras',
        'descricao',
        'ncm',
        'cest',
        'cfop',
        'unidade',
        'preco_venda',
        'preco_custo',
        'origem',
        'cst_icms',
        'csosn',
        'aliq_icms',
        'aliq_ipi',
        'cst_pis',
        'aliq_pis',
        'cst_cofins',
        'aliq_cofins',
        'peso_liquido',
        'peso_bruto',
        'estoque_minimo',
        'observacoes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'preco_venda' => 'decimal:4',
            'preco_custo' => 'decimal:4',
            'aliq_icms' => 'decimal:2',
            'aliq_ipi' => 'decimal:2',
            'aliq_pis' => 'decimal:2',
            'aliq_cofins' => 'decimal:2',
            'peso_liquido' => 'decimal:3',
            'peso_bruto' => 'decimal:3',
            'estoque_minimo' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function estoque(): HasOne
    {
        return $this->hasOne(Estoque::class);
    }
}
