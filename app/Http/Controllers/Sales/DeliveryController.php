<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class DeliveryController extends Controller
{
    use HasListFilters;

    public function __construct(private StockService $stockService) {}

    public function index(Request $request): View
    {
        $query = Delivery::with(['salesOrder.customer', 'warehouse', 'user']);

        $query = $this->applySearch($query, $request, ['delivery_number', 'salesOrder.so_number', 'salesOrder.customer.name', 'recipient_name', 'notes']);
        $query = $this->applyFilter($query, $request, 'warehouse_id');
        $query = $this->applyFilter($query, $request, 'condition_status');
        $query = $this->applyDateRange($query, $request, 'delivery_date');
        $query = $this->applySort($query, $request, ['delivery_number', 'delivery_date', 'condition_status', 'created_at'], 'delivery_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $deliveries = $query->paginate($perPage)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('sales.deliveries.index', compact('deliveries', 'warehouses'));
    }

    public function create(Request $request): View
    {
        $selectedSoId = $request->query('so_id');
        $confirmedSos = SalesOrder::with(['customer', 'items.product'])
            ->whereIn('status', ['confirmed', 'partially_delivered'])
            ->orderByDesc('id')
            ->get();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('sales.deliveries.create', compact('confirmedSos', 'warehouses', 'selectedSoId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sales_order_id'                 => 'required|exists:sales_orders,id',
            'warehouse_id'                  => 'required|exists:warehouses,id',
            'delivery_date'                 => 'required|date',
            'shipping_address'              => 'nullable|string',
            'recipient_name'                => 'nullable|string',
            'notes'                         => 'nullable|string',
            'items'                         => 'required|array|min:1',
            'items.*.sales_order_item_id'   => 'required|exists:sales_order_items,id',
            'items.*.qty_delivered'          => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $so = SalesOrder::with(['items.product', 'items.deliveryItems', 'customer'])->lockForUpdate()->findOrFail($request->sales_order_id);

            $deliveredByItem = [];
            foreach ($request->items as $itemData) {
                $itemId = (int) $itemData['sales_order_item_id'];
                $deliveredByItem[$itemId] = ($deliveredByItem[$itemId] ?? 0) + (int) $itemData['qty_delivered'];
            }

            $hasAnyDelivered = false;
            $productIds = [];
            foreach ($deliveredByItem as $soItemId => $qtyDelivered) {
                $soItem = $so->items->firstWhere('id', $soItemId);
                abort_if(!$soItem, 422, 'Item pengiriman tidak sesuai dengan SO yang dipilih.');

                if ($qtyDelivered > 0) {
                    $hasAnyDelivered = true;
                    $productIds[] = $soItem->product_id;
                }

                if ($qtyDelivered > $soItem->qty_remaining) {
                    return back()
                        ->with('error', "Qty pengiriman '{$soItem->product->name}' melebihi sisa SO. Sisa: {$soItem->qty_remaining}, diinput: {$qtyDelivered}.")
                        ->withInput();
                }
            }

            if (!$hasAnyDelivered) {
                return back()->with('error', 'Isi minimal satu qty barang yang akan dikirim.')->withInput();
            }

            // Lock all involved product rows and active reservations to serialize stock & reservation checks
            Product::whereIn('id', array_unique($productIds))->lockForUpdate()->get();
            \App\Models\StockReservation::whereIn('sales_order_item_id', array_keys($deliveredByItem))
                ->where('warehouse_id', $request->warehouse_id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            // Check stock deliverability under lock (Reservation allocation + Free stock check)
            foreach ($deliveredByItem as $soItemId => $qtyDelivered) {
                if ($qtyDelivered <= 0) continue;
                $soItem = $so->items->firstWhere('id', $soItemId);

                $maxDeliverable = $this->stockService->getDeliverableStockForOrderItem($soItem, $request->warehouse_id);
                if ($qtyDelivered > $maxDeliverable) {
                    $reservedForThis = (int) \App\Models\StockReservation::where('sales_order_item_id', $soItem->id)
                        ->where('warehouse_id', $request->warehouse_id)
                        ->where('status', 'active')
                        ->selectRaw('SUM(qty_reserved - qty_delivered) as active_qty')
                        ->value('active_qty');
                    $freeStock = $this->stockService->getAvailableStock($soItem->product_id, $request->warehouse_id);

                    return back()
                        ->with('error', "Stok untuk produk '{$soItem->product->name}' tidak mencukupi untuk dikirim (Teralokasi untuk SO ini: {$reservedForThis}, Stok Bebas: {$freeStock}, Maksimal Kirim: {$maxDeliverable}, Ingin Dikirim: {$qtyDelivered}).")
                        ->withInput();
                }
            }

            $deliveryNumber = $this->generateNumber();

            $delivery = Delivery::create([
                'delivery_number'  => $deliveryNumber,
                'sales_order_id'   => $so->id,
                'warehouse_id'     => $request->warehouse_id,
                'user_id'          => Auth::id(),
                'condition_status' => 'baik',
                'delivery_date'    => $request->delivery_date,
                'shipping_address' => $request->shipping_address ?? $so->customer->address,
                'recipient_name'   => $request->recipient_name ?? $so->customer->name,
                'notes'            => $request->notes,
            ]);

            foreach ($request->items as $itemData) {
                $soItem = $so->items->firstWhere('id', $itemData['sales_order_item_id']);
                $qtyDelivered = (int) $itemData['qty_delivered'];
                if ($qtyDelivered <= 0) continue;

                DeliveryItem::create([
                    'delivery_id'         => $delivery->id,
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered'       => $qtyDelivered,
                    'condition'           => 'Good',
                ]);

                // Consume active stock reservations for this item with pessimistic lock
                $remainingToFulfill = $qtyDelivered;
                $activeReservations = \App\Models\StockReservation::where('sales_order_item_id', $soItem->id)
                    ->where('warehouse_id', $request->warehouse_id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get();

                foreach ($activeReservations as $res) {
                    $unconsumed = max(0, $res->qty_reserved - $res->qty_delivered);
                    $consume = min($unconsumed, $remainingToFulfill);
                    if ($consume > 0) {
                        $newDelivered = $res->qty_delivered + $consume;
                        $resStatus = ($newDelivered >= $res->qty_reserved) ? 'fulfilled' : 'active';
                        $res->update([
                            'qty_delivered' => $newDelivered,
                            'status'        => $resStatus,
                        ]);
                        $remainingToFulfill -= $consume;
                    }
                    if ($remainingToFulfill <= 0) break;
                }

                // Record stock movement OUT
                $this->stockService->recordMovement([
                    'product_id'     => $soItem->product_id,
                    'warehouse_id'   => $request->warehouse_id,
                    'type'           => 'out',
                    'quantity'       => $qtyDelivered,
                    'unit_cost'      => $soItem->product->purchase_price,
                    'reference_type' => Delivery::class,
                    'reference_id'   => $delivery->id,
                    'movement_date'  => $request->delivery_date,
                    'notes'          => "Surat Jalan / Pengiriman #{$deliveryNumber} (SO #{$so->so_number})",
                    'user_id'        => Auth::id(),
                ]);

                // Sync demand fulfillment (qty_fulfilled & status) for this SO item
                $this->stockService->syncDemandFulfillmentForOrderItem($soItem);
            }

            // Update SO Status & Fulfillment Status
            $so->refresh();
            $this->stockService->refreshSalesOrderFulfillment($so);

            $allDone = true;
            foreach ($so->items as $item) {
                if ($item->qty_delivered < $item->qty_ordered) {
                    $allDone = false;
                    break;
                }
            }

            $so->update([
                'status' => $allDone ? 'done' : 'partially_delivered'
            ]);

            return redirect()->route('sales.deliveries.index')
                ->with('success', 'Surat Jalan berhasil dibuat dan stok barang telah berkurang.');
        });
    }

    public function show(Delivery $delivery): View
    {
        $delivery->load(['salesOrder.customer', 'warehouse', 'user', 'items.salesOrderItem.product']);

        return view('sales.deliveries.show', compact('delivery'));
    }

    public function exportPdf(Delivery $delivery)
    {
        $delivery->load(['salesOrder.customer', 'warehouse', 'user', 'items.salesOrderItem.product']);
        $pdf = Pdf::loadView('pdf.delivery', compact('delivery'));

        return $pdf->download("SJ-{$delivery->delivery_number}.pdf");
    }

    private function generateNumber(): string
    {
        $prefix = 'SJ-' . date('Ym') . '-';
        $last   = Delivery::where('delivery_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('delivery_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
