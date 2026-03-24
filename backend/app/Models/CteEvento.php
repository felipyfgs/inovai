<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CteEvento extends Model
{
    protected $table = 'cte_eventos';

    protected $fillable = [
        'cte_id',
        'tipo',
        'sequencia',
        'protocolo',
        'justificativa',
        'correcao',
        'xml_envio',
        'xml_retorno',
        'status',
    ];

    public function cte(): BelongsTo
    {
        return $this->belongsTo(Cte::class);
    }
}
