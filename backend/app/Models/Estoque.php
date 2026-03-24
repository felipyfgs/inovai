<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estoque extends Model
{
    protected $fillable = [
        'company_id',
        'produto_id',
        'quantidade',
        'custo_medio',
        'localizacao',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:4',
            'custo_medio' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(EstoqueMovimentacao::class);
    }
}
