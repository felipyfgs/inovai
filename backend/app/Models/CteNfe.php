<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CteNfe extends Model
{
    protected $table = 'cte_nfes';

    protected $fillable = [
        'cte_id',
        'chave_nfe',
    ];

    public function cte(): BelongsTo
    {
        return $this->belongsTo(Cte::class);
    }
}
