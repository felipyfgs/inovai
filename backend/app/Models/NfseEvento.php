<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfseEvento extends Model
{
    protected $table = 'nfse_eventos';

    protected $fillable = [
        'nfse_id',
        'tipo',
        'sequencia',
        'protocolo',
        'justificativa',
        'xml_envio',
        'xml_retorno',
        'status',
    ];

    public function nfse(): BelongsTo
    {
        return $this->belongsTo(Nfse::class);
    }
}