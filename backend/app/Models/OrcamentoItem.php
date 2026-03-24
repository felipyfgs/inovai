<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoItem extends Model
{
    protected $table = 'orcamento_itens';

    protected $fillable = [
        'orcamento_id',
        'produto_id',
        'descricao',
        'quantidade',
        'valor_unitario',
        'desconto',
        'valor_total',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:4',
            'valor_unitario' => 'decimal:4',
            'desconto' => 'decimal:2',
            'valor_total' => 'decimal:2',
        ];
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
