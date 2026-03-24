<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Orcamento extends Model
{
    protected $fillable = [
        'company_id',
        'pessoa_id',
        'numero',
        'data',
        'validade',
        'status',
        'observacoes',
        'desconto',
        'valor_total',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'validade' => 'date',
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

    public function itens(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class);
    }

    public function pedido(): HasOne
    {
        return $this->hasOne(Pedido::class);
    }
}
