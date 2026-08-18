<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'sku', 'name', 'category', 'unit',
        'purchase_price', 'sell_price', 'min_stock',
        'is_active', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
    ];

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function salesOrderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    /**
     * Hitung stok berjalan SIAP JUAL di gudang tertentu (atau semua gudang).
     */
    public function currentStock(?int $warehouseId = null): int
    {
        $query = $this->stockMovements();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (int) $query->selectRaw(
            "SUM(CASE
                WHEN type IN ('in','return_in','transfer_in','adjustment') AND quantity > 0 THEN quantity
                WHEN type IN ('out','return_out','transfer_out') THEN -quantity
                WHEN type = 'adjustment' AND quantity < 0 THEN quantity
                ELSE 0
            END) as net_qty"
        )->value('net_qty');
    }

    /**
     * Hitung sisa stok karantina / rusak bersih (minus write_off & reject_out).
     */
    public function quarantineStock(?int $warehouseId = null): int
    {
        $queryIn = $this->stockMovements()->where('type', 'return_in_damaged');
        $queryOut = $this->stockMovements()->whereIn('type', ['write_off', 'reject_out']);

        if ($warehouseId) {
            $queryIn->where('warehouse_id', $warehouseId);
            $queryOut->where('warehouse_id', $warehouseId);
        }

        return max(0, (int) $queryIn->sum('quantity') - (int) $queryOut->sum('quantity'));
    }
}
