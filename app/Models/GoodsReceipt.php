<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'receipt_number', 'purchase_order_id', 'warehouse_id',
        'user_id', 'qc_status', 'is_invoiced', 'purchase_invoice_id',
        'received_date', 'notes',
    ];

    protected $casts = [
        'received_date' => 'date',
        'is_invoiced' => 'boolean',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    /**
     * Apakah LPB ini masih bisa dipilih untuk invoice baru?
     * true jika belum pernah dipakai, false jika sudah.
     */
    public function getIsAvailableForInvoiceAttribute(): bool
    {
        return !$this->is_invoiced;
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function getWarehouseNamesAttribute(): string
    {
        if ($this->relationLoaded('items')) {
            $names = $this->items->map(fn($item) => $item->warehouse?->name)->filter()->unique();
            if ($names->isNotEmpty()) {
                return $names->implode(', ');
            }
        }
        return $this->warehouse?->name ?? '-';
    }
}
