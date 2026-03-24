<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MdfeEvento extends Model
{
    protected $table = 'mdfe_eventos';

    protected $fillable = [
        'mdfe_id',
        'tipo',
        'sequencia',
        'protocolo',
        'justificativa',
        'xml_envio',
        'xml_retorno',
        'status',
    ];

    public function mdfe(): BelongsTo
    {
        return $this->belongsTo(Mdfe::class);
    }
}
