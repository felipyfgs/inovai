<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'office_id',
        'office_plan_id',
        'status',
        'amount',
        'reference',
        'notes',
        'due_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function officePlan(): BelongsTo
    {
        return $this->belongsTo(OfficePlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompanyInvoiceItem::class, 'company_invoice_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_at->isPast();
    }
}
