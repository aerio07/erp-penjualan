<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    protected $appends = ['total_paid', 'total_reversed_amount', 'effective_total_amount', 'outstanding_amount'];

    protected $fillable = [
        'invoice_number', 'purchase_order_id', 'goods_receipt_id',
        'supplier_invoice_number',
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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return in_array($this->status, ['unpaid', 'partial', 'paid']);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function getTotalPaidAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->where('status', '!=', 'draft')->sum('amount');
        }

        return (float) $this->payments()->where('status', '!=', 'draft')->sum('amount');
    }

    public function getTotalReversedAmountAttribute(): float
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return round((float) $items->sum(function (PurchaseInvoiceItem $item) {
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

    public function calculateMaxReturnQty(GoodsReceiptItem $grnItem, string $sourceType = 'accepted'): int
    {
        $physicalLimit = ($sourceType === 'accepted')
            ? (int) $grnItem->qty_available_for_return_accepted
            : (int) $grnItem->qty_available_for_return_rejected;

        $invoiceItem = $grnItem->relationLoaded('purchaseInvoiceItem')
            ? $grnItem->purchaseInvoiceItem
            : ($this->relationLoaded('items')
                ? $this->items->firstWhere('goods_receipt_item_id', $grnItem->id)
                : ($grnItem->purchaseInvoiceItem()->first() ?? $this->items()->where('goods_receipt_item_id', $grnItem->id)->first()));

        if (!$invoiceItem || (int) $invoiceItem->qty_invoiced <= 0) {
            return $physicalLimit;
        }

        $effectiveUnitPrice = ((float) $invoiceItem->subtotal + (float) $invoiceItem->tax_amount) / (int) $invoiceItem->qty_invoiced;
        if ($effectiveUnitPrice <= 0) {
            return $physicalLimit;
        }

        $sisaBelumBayar = (float) $this->effective_total_amount - (float) $this->total_paid;
        $batasNilai = (int) floor(max(0, $sisaBelumBayar) / $effectiveUnitPrice);

        return min($physicalLimit, $batasNilai);
    }
}
