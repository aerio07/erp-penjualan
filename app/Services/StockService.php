<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Catat stock movement dan kembalikan objectnya.
     */
    public function recordMovement(array $data): StockMovement
    {
        return StockMovement::create([
            'product_id'     => $data['product_id'],
            'warehouse_id'   => $data['warehouse_id'],
            'type'           => $data['type'],
            'quantity'       => $data['type'] === 'adjustment' ? $data['quantity'] : abs($data['quantity']),
            'unit_cost'      => $data['unit_cost'] ?? 0,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id'   => $data['reference_id'] ?? null,
            'movement_date'  => $data['movement_date'] ?? now()->toDateString(),
            'notes'          => $data['notes'] ?? null,
            'user_id'        => $data['user_id'],
        ]);
    }

    /**
     * Hitung stok berjalan SIAP JUAL (tidak termasuk barang karantina/rusak).
     */
    public function getCurrentStock(int $productId, ?int $warehouseId = null): int
    {
        $query = StockMovement::where('product_id', $productId);

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
     * Hitung stok fisik KARANTINA (barang rusak/reject hasil retur yang tidak boleh dijual).
     */
    public function getQuarantineStock(int $productId, ?int $warehouseId = null): int
    {
        $query = StockMovement::where('product_id', $productId)
            ->where('type', 'return_in_damaged');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (int) $query->sum('quantity');
    }

    /**
     * Ambil kartu stok (detail per movement).
     */
    public function getStockCard(int $productId, int $warehouseId, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $query = StockMovement::with(['user'])
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->orderBy('movement_date')
            ->orderBy('id');

        if ($dateFrom) {
            $query->whereDate('movement_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('movement_date', '<=', $dateTo);
        }

        $movements = $query->get();
        $runningQty = 0;

        return $movements->map(function ($m) use (&$runningQty) {
            $inTypes  = ['in', 'return_in', 'transfer_in'];
            $outTypes = ['out', 'return_out', 'transfer_out'];

            if (in_array($m->type, $inTypes)) {
                $runningQty += $m->quantity;
            } elseif (in_array($m->type, $outTypes)) {
                $runningQty -= $m->quantity;
            } elseif ($m->type === 'adjustment') {
                $runningQty += $m->quantity;
            }
            // Catatan: type 'return_in_damaged' tidak menambah running_qty siap jual

            return [
                'movement'    => $m,
                'running_qty' => $runningQty,
            ];
        });
    }

    /**
     * Cek apakah stok mencukupi untuk dijual.
     */
    public function isStockSufficient(int $productId, int $warehouseId, int $requiredQty): bool
    {
        return $this->getCurrentStock($productId, $warehouseId) >= $requiredQty;
    }

    /**
     * Ambil semua produk yang stoknya di bawah min_stock.
     */
    public function getLowStockProducts(): \Illuminate\Support\Collection
    {
        return Product::where('is_active', true)
            ->get()
            ->filter(function ($product) {
                $stock = $this->getCurrentStock($product->id);
                return $stock <= $product->min_stock;
            })
            ->map(function ($product) {
                $product->current_stock = $this->getCurrentStock($product->id);
                $product->quarantine_stock = $this->getQuarantineStock($product->id);
                return $product;
            });
    }

    /**
     * Ambil ringkasan stok per produk untuk satu gudang.
     */
    public function getStockByWarehouse(\App\Models\Warehouse $warehouse): \Illuminate\Support\Collection
    {
        return Product::where('is_active', true)
            ->get()
            ->map(function ($product) use ($warehouse) {
                $stock = $this->getCurrentStock($product->id, $warehouse->id);
                $quarantine = $this->getQuarantineStock($product->id, $warehouse->id);
                $product->current_stock   = $stock;
                $product->quarantine_stock = $quarantine;
                $product->stock_value     = $stock * $product->purchase_price;
                return $product;
            })
            ->filter(fn($p) => $p->current_stock > 0 || $p->quarantine_stock > 0 || $p->min_stock > 0)
            ->values();
    }
}
