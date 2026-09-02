<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    protected $fillable = [
        'sku', 'name', 'category_id', 'category', 'unit',
        'purchase_price', 'sell_price', 'min_stock',
        'is_active', 'notes', 'image',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . $this->image);
        }
        return null;
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function purchaseInvoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function salesInvoiceItems(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function purchaseReturnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function salesReturnItems(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

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

    public function reservations(): HasManyThrough
    {
        return $this->hasManyThrough(StockReservation::class, SalesOrderItem::class);
    }

    public function procurementDemands(): HasMany
    {
        return $this->hasMany(ProcurementDemand::class);
    }

    /**
     * Hitung stok fisik ON HAND SIAP JUAL di gudang tertentu (atau semua gudang).
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
     * Alias for on_hand stock
     */
    public function onHandStock(?int $warehouseId = null): int
    {
        return $this->currentStock($warehouseId);
    }

    /**
     * Stok yang aktif dialokasikan / di-reserve untuk SO pelanggan
     */
    public function reservedStock(?int $warehouseId = null): int
    {
        $query = StockReservation::whereHas('salesOrderItem', function ($q) {
            $q->where('product_id', $this->id);
        })->where('status', 'active');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (int) $query->selectRaw('SUM(qty_reserved - qty_delivered) as total_reserved')->value('total_reserved');
    }

    /**
     * Stok bebas untuk order baru (On Hand - Reserved)
     */
    public function availableStock(?int $warehouseId = null): int
    {
        return max(0, $this->onHandStock($warehouseId) - $this->reservedStock($warehouseId));
    }

    /**
     * Defisit pesanan pelanggan yang sedang menunggu pengadaan (backorder)
     */
    public function backorderStock(?int $warehouseId = null): int
    {
        $query = $this->procurementDemands()->whereIn('status', ['pending', 'ordered']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (int) $query->selectRaw('SUM(qty_demanded - qty_fulfilled) as total_backorder')->value('total_backorder');
    }

    /**
     * Kuantitas dalam PO Supplier yang belum tiba di gudang
     */
    public function incomingStock(): int
    {
        return (int) $this->purchaseOrderItems()
            ->whereHas('purchaseOrder', function ($q) {
                $q->whereIn('status', ['confirmed', 'partially_received']);
            })
            ->get()
            ->sum('qty_remaining');
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

        $totalIn = (int) $queryIn->sum('quantity');
        $totalOut = (int) $queryOut->sum('quantity');

        return max(0, $totalIn - $totalOut);
    }
}
