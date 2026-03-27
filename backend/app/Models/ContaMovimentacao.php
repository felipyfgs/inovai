<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaMovimentacao extends Model
{
    protected $table = 'contas_movimentacoes';

    protected $fillable = [
        'conta_id',
        'parcela_id',
        'user_id',
        'tipo',
        'valor',
        'forma_pagamento',
        'observacoes',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data' => 'datetime',
        ];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(ContaParcela::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
