<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaFiscalEvento extends Model
{
    protected $table = 'nota_fiscal_eventos';

    protected $fillable = [
        'nota_fiscal_id',
        'tipo',
        'sequencia',
        'protocolo',
        'justificativa',
        'correcao',
        'xml_envio',
        'xml_retorno',
        'status',
    ];

    public function notaFiscal(): BelongsTo
    {
        return $this->belongsTo(NotaFiscal::class);
    }
}
