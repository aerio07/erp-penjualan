<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'receipt_number', 'purchase_order_id', 'warehouse_id',
        'user_id', 'qc_status', 'received_date', 'notes',
    ];

    protected $casts = ['received_date' => 'date'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
