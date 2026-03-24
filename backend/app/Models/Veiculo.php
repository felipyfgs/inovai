<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Veiculo extends Model
{
    protected $fillable = [
        'company_id',
        'transportadora_id',
        'placa',
        'renavam',
        'uf',
        'tara',
        'capacidade_kg',
        'tipo_veiculo',
        'tipo_carroceria',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tara' => 'decimal:2',
            'capacidade_kg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transportadora(): BelongsTo
    {
        return $this->belongsTo(Transportadora::class);
    }
}
