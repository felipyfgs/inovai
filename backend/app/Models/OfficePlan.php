<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficePlan extends Model
{
    #[Fillable(['office_id', 'name', 'description', 'price', 'max_nfs_month', 'modules', 'is_active', 'is_default'])]
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_nfs_month' => 'integer',
            'modules' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
