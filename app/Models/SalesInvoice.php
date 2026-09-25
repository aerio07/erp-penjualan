<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\SalesInvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $appends = ['total_paid', 'total_reversed_amount', 'effective_total_amount', 'total_debit_note_amount', 'outstanding_amount'];

    protected $fillable = [
        'invoice_number', 'tax_invoice_number', 'sales_order_id', 'delivery_id',
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
        'status' => SalesInvoiceStatus::class,
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalesPayment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function debitNoteInvoices(): HasMany
    {
        return $this->hasMany(DebitNoteInvoice::class);
    }

    public function getTotalPaidAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->filter(fn($p) => $p->status !== PaymentStatus::Draft)->sum('amount');
        }

        return (float) $this->payments()->where('status', '!=', PaymentStatus::Draft->value)->sum('amount');
    }

    public function getTotalDebitNoteAmountAttribute(): float
    {
        if ($this->relationLoaded('debitNoteInvoices')) {
            return (float) $this->debitNoteInvoices
                ->filter(fn($dni) => $dni->debitNote && $dni->debitNote->status === 'posted')
                ->sum('dn_amount');
        }

        return (float) $this->debitNoteInvoices()
            ->whereHas('debitNote', fn($q) => $q->where('status', 'posted'))
            ->sum('dn_amount');
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
        return max(0, round($this->effective_total_amount - $this->total_paid - $this->total_debit_note_amount, 2));
    }

    public function calculateMaxReturnQty(DeliveryItem $deliveryItem): int
    {
        $invoiceItem = $deliveryItem->relationLoaded('salesInvoiceItem')
            ? $deliveryItem->salesInvoiceItem
            : ($this->relationLoaded('items')
                ? $this->items->firstWhere('delivery_item_id', $deliveryItem->id)
                : ($deliveryItem->salesInvoiceItem()->first() ?? $this->items()->where('delivery_item_id', $deliveryItem->id)->first()));

        if (!$invoiceItem || (int) $invoiceItem->qty_invoiced <= 0) {
            return $deliveryItem->qty_available_for_return;
        }

        $effectiveUnitPrice = ((float) $invoiceItem->subtotal + (float) $invoiceItem->tax_amount) / (int) $invoiceItem->qty_invoiced;
        if ($effectiveUnitPrice <= 0) {
            return $deliveryItem->qty_available_for_return;
        }

        $sisaBelumBayar = (float) $this->effective_total_amount - (float) $this->total_paid;
        $batasNilai = (int) floor(max(0, $sisaBelumBayar) / $effectiveUnitPrice);

        return min($deliveryItem->qty_available_for_return, $batasNilai);
    }

    public function isDraft(): bool
    {
        return $this->status->isDraft();
    }

    public function isPosted(): bool
    {
        return $this->status->isPosted();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status instanceof SalesInvoiceStatus ? $this->status->label() : (string) $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status instanceof SalesInvoiceStatus ? $this->status->badge() : 'badge-secondary';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status_badge;
    }

    public function isPaid(): bool
    {
        return $this->status === SalesInvoiceStatus::Paid;
    }
}
