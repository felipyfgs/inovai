<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfeEvento extends Model
{
    protected $table = 'nfe_eventos';

    protected $fillable = [
        'nfe_id',
        'tipo',
        'sequencia',
        'protocolo',
        'justificativa',
        'correcao',
        'xml_envio',
        'xml_retorno',
        'status',
    ];

    public function nfe(): BelongsTo
    {
        return $this->belongsTo(Nfe::class);
    }
}
