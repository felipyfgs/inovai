<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conta extends Model
{
    protected $fillable = [
        'company_id',
        'tipo',
        'pessoa_id',
        'descricao',
        'documento',
        'pedido_id',
        'categoria',
        'data_emissao',
        'data_vencimento',
        'data_baixa',
        'valor_original',
        'valor_desconto',
        'valor_juros',
        'valor_multa',
        'valor_baixado',
        'status',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'data_vencimento' => 'date',
            'data_baixa' => 'date',
            'valor_original' => 'decimal:2',
            'valor_desconto' => 'decimal:2',
            'valor_juros' => 'decimal:2',
            'valor_multa' => 'decimal:2',
            'valor_baixado' => 'decimal:2',
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

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(ContaParcela::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(ContaMovimentacao::class);
    }

    public function getValorRestanteAttribute(): string
    {
        return bcsub($this->valor_original, $this->valor_baixado, 2);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('company', function ($query) {
            if ($company = app('current_company')) {
                $query->where('company_id', $company->id);
            }
        });
    }
}
