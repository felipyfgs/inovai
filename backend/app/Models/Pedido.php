<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $fillable = [
        'company_id',
        'pessoa_id',
        'orcamento_id',
        'numero',
        'data',
        'status',
        'observacoes',
        'desconto',
        'valor_total',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'desconto' => 'decimal:2',
            'valor_total' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function nfes(): HasMany
    {
        return $this->hasMany(Nfe::class);
    }
}
