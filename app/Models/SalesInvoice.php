<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $appends = ['total_paid', 'outstanding_amount'];

    protected $fillable = [
        'invoice_number', 'sales_order_id',
        'amount', 'tax_rate', 'tax_amount', 'total_amount',
        'invoice_date', 'due_date', 'status', 'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalesPayment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function getTotalPaidAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }

        return (float) $this->payments()->sum('amount');
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->total_paid);
    }
}
