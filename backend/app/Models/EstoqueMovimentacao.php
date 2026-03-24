<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstoqueMovimentacao extends Model
{
    protected $table = 'estoque_movimentacoes';

    protected $fillable = [
        'estoque_id',
        'user_id',
        'tipo',
        'quantidade',
        'custo_unitario',
        'documento_tipo',
        'documento_id',
        'observacoes',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:4',
            'custo_unitario' => 'decimal:4',
            'data' => 'datetime',
        ];
    }

    public function estoque(): BelongsTo
    {
        return $this->belongsTo(Estoque::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
