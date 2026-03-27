<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfseItem extends Model
{
    protected $table = 'nfse_itens';

    protected $fillable = [
        'nfse_id',
        'numero_item',
        'discriminacao',
        'quantidade',
        'unidade',
        'valor_unitario',
        'valor_total',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:4',
            'valor_unitario' => 'decimal:4',
            'valor_total' => 'decimal:2',
        ];
    }

    public function nfse(): BelongsTo
    {
        return $this->belongsTo(Nfse::class);
    }
}
