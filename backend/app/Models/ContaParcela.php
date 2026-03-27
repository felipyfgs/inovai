<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaParcela extends Model
{
    protected $table = 'contas_parcelas';

    protected $fillable = [
        'conta_id',
        'numero',
        'data_vencimento',
        'data_baixa',
        'valor',
        'valor_desconto',
        'valor_juros',
        'valor_multa',
        'valor_baixado',
        'forma_pagamento',
        'observacoes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'data_vencimento' => 'date',
            'data_baixa' => 'date',
            'valor' => 'decimal:2',
            'valor_desconto' => 'decimal:2',
            'valor_juros' => 'decimal:2',
            'valor_multa' => 'decimal:2',
            'valor_baixado' => 'decimal:2',
        ];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(ContaMovimentacao::class);
    }
}
