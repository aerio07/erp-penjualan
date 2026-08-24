<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProcurementDemand;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
     * Dimensi 1: ON HAND - Hitung stok fisik berjalan SIAP JUAL (tidak termasuk barang karantina/rusak).
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

    public function getOnHandStock(int $productId, ?int $warehouseId = null): int
    {
        return $this->getCurrentStock($productId, $warehouseId);
    }

    /**
     * Dimensi 2: RESERVED - Stok fisik yang benar-benar aktif dialokasikan / di-booking untuk SO pelanggan.
     */
    public function getReservedStock(int $productId, ?int $warehouseId = null): int
    {
        $query = StockReservation::whereHas('salesOrderItem', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })->where('status', 'active');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (int) $query->selectRaw('SUM(qty_reserved - qty_delivered) as total_reserved')->value('total_reserved');
    }

    /**
     * Dimensi 3: AVAILABLE - Stok fisik bebas yang belum teralokasi (On Hand - Reserved).
     */
    public function getAvailableStock(int $productId, ?int $warehouseId = null): int
    {
        return max(0, $this->getOnHandStock($productId, $warehouseId) - $this->getReservedStock($productId, $warehouseId));
    }

    /**
     * Dimensi 4: BACKORDER - Defisit demand pelanggan yang belum mendapatkan alokasi stok (menunggu pengadaan).
     */
    public function getBackorderStock(int $productId, ?int $warehouseId = null): int
    {
        $query = ProcurementDemand::where('product_id', $productId)
            ->whereIn('status', ['pending', 'ordered']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (int) $query->selectRaw('SUM(qty_demanded - qty_fulfilled) as total_backorder')->value('total_backorder');
    }

    /**
     * Dimensi 5: INCOMING - Barang dalam PO Supplier yang sudah dikonfirmasi tapi belum diterima via GRN.
     */
    public function getIncomingStock(int $productId): int
    {
        return (int) PurchaseOrderItem::where('product_id', $productId)
            ->whereHas('purchaseOrder', function ($q) {
                $q->whereIn('status', ['confirmed', 'partially_received']);
            })
            ->get()
            ->sum('qty_remaining');
    }

    /**
     * Evaluasi dan alokasikan stok untuk Sales Order saat dikonfirmasi.
     */
    public function allocateStockForSalesOrder(SalesOrder $order, ?int $preferredWarehouseId = null): void
    {
        $order->load(['customer', 'items.product', 'items.reservations', 'items.procurementDemands']);

        // Tentukan gudang default jika tidak dispesifikasi
        if (!$preferredWarehouseId) {
            $preferredWarehouseId = Warehouse::where('is_active', true)->orderBy('id')->value('id');
        }

        foreach ($order->items as $item) {
            $neededQty = $item->qty_remaining;
            $currentReserved = (int) $item->reservations->where('status', 'active')->sum(fn($r) => max(0, $r->qty_reserved - $r->qty_delivered));
            $stillNeeded = max(0, $neededQty - $currentReserved);

            if ($stillNeeded <= 0) {
                continue;
            }

            $availableInWarehouse = $this->getAvailableStock($item->product_id, $preferredWarehouseId);

            // Alokasikan apa yang ada di gudang
            $allocatable = min($availableInWarehouse, $stillNeeded);

            if ($allocatable > 0) {
                StockReservation::create([
                    'sales_order_item_id' => $item->id,
                    'warehouse_id'        => $preferredWarehouseId,
                    'qty_reserved'        => $allocatable,
                    'qty_delivered'       => 0,
                    'status'              => 'active',
                ]);
            }

            // Defisit dicatat sebagai Procurement Demand (Backorder)
            $deficit = $stillNeeded - $allocatable;
            if ($deficit > 0) {
                $existingDemand = ProcurementDemand::where('sales_order_item_id', $item->id)
                    ->whereIn('status', ['pending', 'ordered'])
                    ->first();

                if ($existingDemand) {
                    $existingDemand->update([
                        'qty_demanded' => $existingDemand->qty_demanded + $deficit,
                    ]);
                } else {
                    $demandNumber = $this->generateDemandNumber();
                    ProcurementDemand::create([
                        'demand_number'       => $demandNumber,
                        'sales_order_id'      => $order->id,
                        'sales_order_item_id' => $item->id,
                        'product_id'          => $item->product_id,
                        'warehouse_id'        => $preferredWarehouseId,
                        'qty_demanded'        => $deficit,
                        'qty_procured'        => 0,
                        'qty_fulfilled'       => 0,
                        'status'              => 'pending',
                        'required_date'       => $order->expected_delivery_date ?? $order->order_date,
                        'notes'               => "Kebutuhan dari SO #{$order->so_number} untuk " . ($order->customer->name ?? '-'),
                    ]);
                }
            }
        }

        $this->refreshSalesOrderFulfillment($order);
    }

    /**
     * Lepas reservasi stok dan batalkan demand saat SO dibatalkan.
     */
    public function releaseReservationsForSalesOrder(SalesOrder $order): void
    {
        $order->load(['items.reservations', 'items.procurementDemands']);

        $affectedProducts = [];

        foreach ($order->items as $item) {
            $affectedProducts[$item->product_id] = $item->product_id;

            // Batalkan semua reservasi aktif
            StockReservation::where('sales_order_item_id', $item->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            // Batalkan semua procurement demand aktif
            ProcurementDemand::where('sales_order_item_id', $item->id)
                ->whereIn('status', ['pending', 'ordered'])
                ->update(['status' => 'cancelled']);
        }

        $order->update(['fulfillment_status' => 'pending']);

        // Alokasikan stok yang baru bebas ke SO lain yang sedang antre backorder
        foreach ($affectedProducts as $productId) {
            $this->allocateStockToPendingDemands($productId);
        }
    }

    /**
     * Targeted Event-Driven Allocation: Alokasikan stok masuk (dari GRN atau pembatalan SO) ke demand backorder secara FIFO.
     */
    public function allocateStockToPendingDemands(int $productId, ?int $warehouseId = null): void
    {
        $query = ProcurementDemand::with(['salesOrder', 'salesOrderItem'])
            ->where('procurement_demands.product_id', $productId)
            ->whereIn('procurement_demands.status', ['pending', 'ordered'])
            ->join('sales_orders', 'procurement_demands.sales_order_id', '=', 'sales_orders.id')
            ->orderBy('sales_orders.order_date', 'asc')
            ->orderBy('sales_orders.id', 'asc')
            ->select('procurement_demands.*');

        if ($warehouseId) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('procurement_demands.warehouse_id', $warehouseId)
                  ->orWhereNull('procurement_demands.warehouse_id');
            });
        }

        $demands = $query->get();
        $affectedSalesOrders = [];

        foreach ($demands as $demand) {
            $targetWarehouseId = $demand->warehouse_id ?? $warehouseId ?? Warehouse::where('is_active', true)->orderBy('id')->value('id');
            $freeStock = $this->getAvailableStock($productId, $targetWarehouseId);

            if ($freeStock <= 0) {
                break; // Stok sudah habis dialokasikan
            }

            $unfulfilled = max(0, $demand->qty_demanded - $demand->qty_fulfilled);
            if ($unfulfilled <= 0) {
                continue;
            }

            $allocatable = min($freeStock, $unfulfilled);

            if ($allocatable > 0) {
                StockReservation::create([
                    'sales_order_item_id' => $demand->sales_order_item_id,
                    'warehouse_id'        => $targetWarehouseId,
                    'qty_reserved'        => $allocatable,
                    'qty_delivered'       => 0,
                    'status'              => 'active',
                ]);

                $newFulfilled = $demand->qty_fulfilled + $allocatable;
                $demandStatus = ($newFulfilled >= $demand->qty_demanded) ? 'fulfilled' : $demand->status;

                $demand->update([
                    'qty_fulfilled' => $newFulfilled,
                    'status'        => $demandStatus,
                ]);

                $affectedSalesOrders[$demand->sales_order_id] = $demand->salesOrder;
            }
        }

        // Refresh fulfillment_status HANYA untuk SO yang terdampak
        foreach ($affectedSalesOrders as $so) {
            if ($so) {
                $this->refreshSalesOrderFulfillment($so);
            }
        }
    }

    /**
     * Sinkronisasi status dan qty_fulfilled untuk procurement demand sebuah item Sales Order.
     * Memperhitungkan kuantitas yang sudah dikirim (delivered) dan kuantitas aktif ter-reserve dari pengadaan.
     */
    public function syncDemandFulfillmentForOrderItem(SalesOrderItem $soItem): void
    {
        $soItem->load(['deliveryItems', 'reservations', 'procurementDemands']);

        $demands = $soItem->procurementDemands->where('status', '!=', 'cancelled')->sortBy('id');
        if ($demands->isEmpty()) {
            return;
        }

        $totalDemanded = (int) $demands->sum('qty_demanded');
        // Bagian pesanan yang bukan merupakan defisit demand (sudah ada stok awalnya saat SO dikonfirmasi)
        $initialInStockQty = max(0, $soItem->qty_ordered - $totalDemanded);
        $deliveredQty = (int) $soItem->deliveryItems->sum('qty_delivered');
        // Pengiriman yang dialokasikan untuk menutupi demand
        $deliveredForDemands = max(0, $deliveredQty - $initialInStockQty);

        // Kuantitas aktif ter-reserve (misalnya dari GRN yang belum dikirim)
        $activeReserved = (int) $soItem->reservations
            ->where('status', 'active')
            ->sum(fn($r) => max(0, $r->qty_reserved - $r->qty_delivered));

        $totalFulfilled = min($totalDemanded, $deliveredForDemands + $activeReserved);

        $remainingToDistribute = $totalFulfilled;

        foreach ($demands as $demand) {
            $demanded = $demand->qty_demanded;
            $fulfilled = min($demanded, $remainingToDistribute);
            $remainingToDistribute = max(0, $remainingToDistribute - $fulfilled);

            $newStatus = ($fulfilled >= $demanded)
                ? 'fulfilled'
                : ($demand->purchase_order_id ? 'ordered' : 'pending');

            if ($demand->qty_fulfilled !== $fulfilled || $demand->status !== $newStatus) {
                $demand->update([
                    'qty_fulfilled' => $fulfilled,
                    'status'        => $newStatus,
                ]);
            }
        }
    }

    /**
     * Sinkronisasi seluruh procurement demand aktif dalam sistem.
     */
    public function syncAllProcurementDemands(): void
    {
        $orderItems = SalesOrderItem::whereHas('procurementDemands', function ($q) {
            $q->where('status', '!=', 'cancelled');
        })->with(['deliveryItems', 'reservations', 'procurementDemands'])->get();

        foreach ($orderItems as $item) {
            $this->syncDemandFulfillmentForOrderItem($item);
        }
    }

    /**
     * Hitung status pemenuhan berdasarkan kuantitas terkirim dan kuantitas ter-reserve.
     */
    public function evaluateSalesOrderFulfillmentStatus(SalesOrder $order): string
    {
        $order->load(['items.deliveryItems', 'items.reservations', 'items.procurementDemands']);

        if ($order->status === 'draft' || $order->status === 'waiting_approval' || $order->status === 'cancelled') {
            return 'pending';
        }

        $allDelivered = true;
        $anyDelivered = false;
        $allReadyToShip = true;
        $totalReserved = 0;
        $totalRemaining = 0;

        foreach ($order->items as $item) {
            $ordered = $item->qty_ordered;
            $delivered = $item->qty_delivered;
            $remaining = max(0, $ordered - $delivered);
            $reserved = $item->qty_reserved;

            $totalRemaining += $remaining;
            $totalReserved += $reserved;

            if ($delivered > 0) {
                $anyDelivered = true;
            }

            if ($remaining > 0) {
                $allDelivered = false;
            }

            if ($reserved < $remaining) {
                $allReadyToShip = false;
            }
        }

        if ($allDelivered && $order->items->isNotEmpty()) {
            return 'delivered';
        }

        if ($anyDelivered && !$allDelivered) {
            return 'partially_delivered';
        }

        if ($allReadyToShip && $totalRemaining > 0) {
            return 'ready_to_ship';
        }

        if ($totalReserved > 0) {
            return 'partially_available';
        }

        return 'backorder';
    }

    /**
     * Refresh dan simpan status fulfillment ke database.
     */
    public function refreshSalesOrderFulfillment(SalesOrder $order): void
    {
        $newStatus = $this->evaluateSalesOrderFulfillmentStatus($order);
        $order->update(['fulfillment_status' => $newStatus]);
    }

    /**
     * Hitung stok fisik KARANTINA BERSIH (barang rusak/reject minus write_off & reject_out).
     */
    public function getQuarantineStock(int $productId, ?int $warehouseId = null): int
    {
        $queryIn = StockMovement::where('product_id', $productId)
            ->where('type', 'return_in_damaged');

        $queryOut = StockMovement::where('product_id', $productId)
            ->whereIn('type', ['write_off', 'reject_out']);

        if ($warehouseId) {
            $queryIn->where('warehouse_id', $warehouseId);
            $queryOut->where('warehouse_id', $warehouseId);
        }

        return max(0, (int) $queryIn->sum('quantity') - (int) $queryOut->sum('quantity'));
    }

    public function getQuarantineStockAvailable(int $productId, ?int $warehouseId = null): int
    {
        return $this->getQuarantineStock($productId, $warehouseId);
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

            return [
                'movement'    => $m,
                'running_qty' => $runningQty,
            ];
        });
    }

    public function isStockSufficient(int $productId, int $warehouseId, int $requiredQty): bool
    {
        return $this->getCurrentStock($productId, $warehouseId) >= $requiredQty;
    }

    public function getLowStockProducts(): Collection
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

    public function getStockByWarehouse(Warehouse $warehouse): Collection
    {
        return Product::where('is_active', true)
            ->get()
            ->map(function ($product) use ($warehouse) {
                $onHand     = $this->getOnHandStock($product->id, $warehouse->id);
                $reserved   = $this->getReservedStock($product->id, $warehouse->id);
                $available  = $this->getAvailableStock($product->id, $warehouse->id);
                $backorder  = $this->getBackorderStock($product->id, $warehouse->id);
                $incoming   = $this->getIncomingStock($product->id);
                $quarantine = $this->getQuarantineStock($product->id, $warehouse->id);

                $product->on_hand_stock    = $onHand;
                $product->current_stock    = $onHand;
                $product->reserved_stock   = $reserved;
                $product->available_stock  = $available;
                $product->backorder_stock  = $backorder;
                $product->incoming_stock   = $incoming;
                $product->quarantine_stock = $quarantine;
                $product->stock_value      = $onHand * $product->purchase_price;

                return $product;
            })
            ->filter(fn($p) => $p->on_hand_stock > 0 || $p->reserved_stock > 0 || $p->backorder_stock > 0 || $p->quarantine_stock > 0 || $p->min_stock > 0)
            ->values();
    }

    /**
     * Sinkronisasi & alokasi stok untuk semua SO aktif yang belum dialokasikan.
     */
    public function syncAllPendingSalesOrders(): void
    {
        $orders = SalesOrder::whereIn('status', ['confirmed', 'partially_delivered'])
            ->where(function ($q) {
                $q->whereNull('fulfillment_status')
                  ->orWhere('fulfillment_status', 'pending');
            })
            ->orderBy('order_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($orders as $order) {
            $this->allocateStockForSalesOrder($order);
        }
    }

    private function generateDemandNumber(): string
    {
        $prefix = 'DEM-' . date('Ym') . '-';
        $last   = ProcurementDemand::where('demand_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('demand_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
