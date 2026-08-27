<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $appends = ['total_paid', 'total_reversed_amount', 'effective_total_amount', 'outstanding_amount'];

    protected $fillable = [
        'invoice_number', 'tax_invoice_number', 'sales_order_id',
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

    public function getTotalReversedAmountAttribute(): float
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return round((float) $items->sum(function (SalesInvoiceItem $item) {
            if ($item->qty_invoiced <= 0 || $item->reversed_qty <= 0) {
                return 0;
            }

            $reversedQty = min((int) $item->reversed_qty, (int) $item->qty_invoiced);
            $lineTotal = (float) $item->subtotal + (float) $item->tax_amount;

            return ($lineTotal / (int) $item->qty_invoiced) * $reversedQty;
        }), 2);
    }

    public function getEffectiveTotalAmountAttribute(): float
    {
        return max(0, round((float) $this->total_amount - $this->total_reversed_amount, 2));
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, round($this->effective_total_amount - $this->total_paid, 2));
    }
}
