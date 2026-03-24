<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotaFiscal extends Model
{
    protected $table = 'notas_fiscais';

    protected $fillable = [
        'company_id',
        'pessoa_id',
        'pedido_id',
        'transportadora_id',
        'modelo',
        'serie',
        'numero',
        'chave',
        'natureza_operacao',
        'tipo_operacao',
        'finalidade',
        'data_emissao',
        'data_saida',
        'status',
        'ambiente',
        'valor_produtos',
        'valor_frete',
        'valor_seguro',
        'valor_desconto',
        'valor_outras',
        'valor_icms',
        'valor_icms_st',
        'valor_ipi',
        'valor_pis',
        'valor_cofins',
        'valor_total',
        'frete_por',
        'xml_envio',
        'xml_retorno',
        'xml_autorizado',
        'protocolo',
        'recibo',
        'motivo',
        'informacoes_adicionais',
        'informacoes_fisco',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'data_saida' => 'datetime',
            'valor_produtos' => 'decimal:2',
            'valor_frete' => 'decimal:2',
            'valor_seguro' => 'decimal:2',
            'valor_desconto' => 'decimal:2',
            'valor_outras' => 'decimal:2',
            'valor_icms' => 'decimal:2',
            'valor_icms_st' => 'decimal:2',
            'valor_ipi' => 'decimal:2',
            'valor_pis' => 'decimal:2',
            'valor_cofins' => 'decimal:2',
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

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function transportadora(): BelongsTo
    {
        return $this->belongsTo(Transportadora::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(NotaFiscalItem::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(NotaFiscalEvento::class);
    }

    public function isNfe(): bool
    {
        return $this->modelo === 55;
    }

    public function isNfce(): bool
    {
        return $this->modelo === 65;
    }

    public function isAutorizada(): bool
    {
        return $this->status === 'autorizada';
    }
}
