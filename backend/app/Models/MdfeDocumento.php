<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MdfeDocumento extends Model
{
    protected $table = 'mdfe_documentos';

    protected $fillable = [
        'mdfe_id',
        'tipo',
        'chave',
    ];

    public function mdfe(): BelongsTo
    {
        return $this->belongsTo(Mdfe::class);
    }
}
