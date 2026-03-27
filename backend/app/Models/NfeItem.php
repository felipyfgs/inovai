<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfeItem extends Model
{
    protected $table = 'nfe_itens';

    protected $fillable = [
        'nfe_id',
        'produto_id',
        'numero_item',
        'codigo',
        'descricao',
        'ncm',
        'cest',
        'cfop',
        'unidade',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'valor_desconto',
        'valor_frete',
        'valor_seguro',
        'valor_outras',
        'origem',
        'cst_icms',
        'csosn',
        'bc_icms',
        'aliq_icms',
        'valor_icms',
        'bc_icms_st',
        'aliq_icms_st',
        'valor_icms_st',
        'cst_ipi',
        'bc_ipi',
        'aliq_ipi',
        'valor_ipi',
        'cst_pis',
        'bc_pis',
        'aliq_pis',
        'valor_pis',
        'cst_cofins',
        'bc_cofins',
        'aliq_cofins',
        'valor_cofins',
    ];

    public function nfe(): BelongsTo
    {
        return $this->belongsTo(Nfe::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
