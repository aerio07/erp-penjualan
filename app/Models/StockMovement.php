<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'warehouse_id', 'type', 'quantity',
        'unit_cost', 'reference_type', 'reference_id',
        'movement_date', 'notes', 'user_id',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'unit_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function getSourceUrlAttribute(): ?string
    {
        if (!$this->reference_id || !$this->reference_type) {
            return null;
        }

        try {
            return match ($this->reference_type) {
                StockOpname::class, 'App\Models\StockOpname' => route('inventory.opname.show', $this->reference_id),
                GoodsReceipt::class, 'App\Models\GoodsReceipt' => route('purchase.goods-receipts.show', $this->reference_id),
                Delivery::class, 'App\Models\Delivery' => route('sales.deliveries.show', $this->reference_id),
                WarehouseTransfer::class, 'App\Models\WarehouseTransfer' => route('inventory.transfers.show', $this->reference_id),
                PurchaseReturn::class, 'App\Models\PurchaseReturn' => route('purchase.returns.show', $this->reference_id),
                SalesReturn::class, 'App\Models\SalesReturn' => route('sales.returns.show', $this->reference_id),
                default => null,
            };
        } catch (\Throwable $e) {
            return null;
        }
    }
}
