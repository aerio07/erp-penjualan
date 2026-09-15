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
use App\Traits\GeneratesDocumentNumber;
use App\Traits\ExportsPdfDocument;

class DeliveryController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber, ExportsPdfDocument;

    public function __construct(
        private StockService $stockService,
        private \App\Services\SalesInvoiceService $salesInvoiceService
    ) {}

    public function index(Request $request): View
    {
        $query = Delivery::with(['salesOrder.customer', 'warehouse', 'user', 'salesInvoice']);

        $query = $this->applySearch($query, $request, ['delivery_number', 'salesOrder.so_number', 'salesOrder.customer.name', 'recipient_name', 'notes']);
        $query = $this->applyFilter($query, $request, 'warehouse_id');
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'condition_status');
        $query = $this->applyDateRange($query, $request, 'delivery_date');
        $query = $this->applySort($query, $request, ['delivery_number', 'delivery_date', 'status', 'condition_status', 'created_at'], 'delivery_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $deliveries = $query->paginate($perPage)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('sales.deliveries.index', compact('deliveries', 'warehouses'));
    }

    public function create(Request $request): View
    {
        $selectedSoId = old('sales_order_id', $request->query('so_id'));
        $confirmedSos = SalesOrder::with(['customer', 'items.product', 'items.reservations'])
            ->whereIn('status', ['confirmed', 'partially_delivered'])
            ->orderByDesc('id')
            ->get();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        // Siapkan stock matrix per SO untuk seluruh gudang aktif
        $stockMatrixBySo = [];
        foreach ($confirmedSos as $so) {
            $stockMatrixBySo[$so->id] = $this->stockService->getWarehouseStockMatrixForOrder($so);
        }

        return view('sales.deliveries.create', compact('confirmedSos', 'warehouses', 'selectedSoId', 'stockMatrixBySo'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sales_order_id'                 => 'required|exists:sales_orders,id',
            'warehouse_id'                   => 'required|exists:warehouses,id',
            'delivery_date'                  => 'required|date',
            'shipping_address'               => 'nullable|string',
            'recipient_name'                 => 'nullable|string',
            'notes'                          => 'nullable|string',
            'auto_generate_invoice'          => 'nullable|boolean',
            'received_date'                  => 'nullable|date|after_or_equal:delivery_date',
            'invoice_date'                   => 'nullable|date',
            'due_date'                       => 'nullable|date|after_or_equal:invoice_date',
            'invoice_tax_rate'               => 'nullable|numeric|min:0|max:100',
            'receipt_notes'                  => 'nullable|string',
            'items'                          => 'required|array|min:1',
            'items.*.sales_order_item_id'   => 'required|exists:sales_order_items,id',
            'items.*.qty_delivered'          => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $so = SalesOrder::with(['items.product', 'items.deliveryItems', 'items.reservations', 'customer'])->lockForUpdate()->findOrFail($request->sales_order_id);

            $deliveredByItem = [];
            foreach ($request->items as $itemData) {
                $itemId = (int) $itemData['sales_order_item_id'];
                $deliveredByItem[$itemId] = ($deliveredByItem[$itemId] ?? 0) + (int) $itemData['qty_delivered'];
            }

            $hasAnyDelivered = false;
            $productIds = [];
            foreach ($deliveredByItem as $soItemId => $qtyDelivered) {
                $soItem = $so->items->firstWhere('id', $soItemId);
                if (!$soItem) {
                    return back()->with('error', 'Item pengiriman tidak sesuai dengan SO yang dipilih.')->withInput();
                }

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

            // Pessimistic Lock: Kunci baris Produk, Item SO, dan seluruh Reservasi aktif untuk SO ini
            Product::whereIn('id', array_unique($productIds))->lockForUpdate()->get();
            SalesOrderItem::where('sales_order_id', $so->id)->lockForUpdate()->get();
            \App\Models\StockReservation::whereIn('sales_order_item_id', $so->items->pluck('id'))
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            $warehouse = Warehouse::findOrFail($request->warehouse_id);

            // Validasi deliverable stock di gudang yang dipilih under pessimistic lock
            foreach ($deliveredByItem as $soItemId => $qtyDelivered) {
                if ($qtyDelivered <= 0) continue;
                $soItem = $so->items->firstWhere('id', $soItemId);

                $maxDeliverable = $this->stockService->getDeliverableStockForOrderItem($soItem, $request->warehouse_id);
                if ($qtyDelivered > $maxDeliverable) {
                    $onHand = $this->stockService->getOnHandStock($soItem->product_id, $request->warehouse_id);
                    $reservedForThis = (int) \App\Models\StockReservation::where('sales_order_item_id', $soItem->id)
                        ->where('warehouse_id', $request->warehouse_id)
                        ->where('status', 'active')
                        ->selectRaw('SUM(qty_reserved - qty_delivered) as active_qty')
                        ->value('active_qty');
                    $freeStock = $this->stockService->getAvailableStock($soItem->product_id, $request->warehouse_id);

                    return back()
                        ->with('error', "Stok untuk produk '{$soItem->product->name}' di {$warehouse->name} tidak mencukupi untuk dikirim (Stok Fisik: {$onHand}, Teralokasi untuk SO ini: {$reservedForThis}, Stok Bebas: {$freeStock}, Maksimal Kirim: {$maxDeliverable}, Ingin Dikirim: {$qtyDelivered}).")
                        ->withInput();
                }
            }

            $deliveryNumber = $this->generateNumber();

            $isAutoInvoice = $request->boolean('auto_generate_invoice');
            $status = $isAutoInvoice ? 'delivered' : 'in_transit';
            $receivedDate = $isAutoInvoice ? ($request->received_date ?? $request->delivery_date) : null;
            $receiptNotes = $isAutoInvoice ? ($request->receipt_notes ?? 'Dikonfirmasi langsung saat pembuatan Surat Jalan') : null;

            $delivery = Delivery::create([
                'delivery_number'  => $deliveryNumber,
                'sales_order_id'   => $so->id,
                'warehouse_id'     => $request->warehouse_id,
                'user_id'          => Auth::id(),
                'condition_status' => 'baik',
                'status'           => $status,
                'delivery_date'    => $request->delivery_date,
                'received_date'    => $receivedDate,
                'receipt_notes'    => $receiptNotes,
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

                // Konsumsi reservasi aktif dan seimbangkan lintas gudang jika memakai stok bebas
                $this->stockService->consumeDeliveryReservations($soItem, $request->warehouse_id, $qtyDelivered);

                // Record stock movement OUT pada gudang pengiriman terpilih
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

            if ($isAutoInvoice) {
                $delivery->loadMissing(['salesOrder.customer']);
                $invDate = $request->invoice_date ?? $receivedDate;
                $termDays = $delivery->salesOrder?->customer?->payment_term_days ?? 30;
                $calcDueDate = $request->due_date ?? \Carbon\Carbon::parse($invDate)->addDays($termDays)->toDateString();

                $invoice = $this->salesInvoiceService->createFromDelivery($delivery, [
                    'invoice_date' => $invDate,
                    'due_date'     => $calcDueDate,
                    'tax_rate'     => $request->filled('invoice_tax_rate') ? (float) $request->invoice_tax_rate : (float) ($so->tax_rate ?? 0),
                    'notes'        => $request->notes ?? ("Auto-generated dari Surat Jalan #{$deliveryNumber}"),
                ], asDraft: false);

                return redirect()->route('sales.deliveries.index')
                    ->with('success', "Surat Jalan #{$delivery->delivery_number} berhasil dibuat (status Diterima) dan Invoice Penjualan #{$invoice->invoice_number} berhasil diterbitkan secara otomatis.");
            }

            return redirect()->route('sales.deliveries.index')
                ->with('success', "Surat Jalan #{$delivery->delivery_number} berhasil dibuat dengan status Dalam Pengiriman (In Transit). Stok barang telah dikurangi.");
        });
    }

    public function confirmReceipt(Request $request, Delivery $delivery): RedirectResponse
    {
        if ($delivery->status === 'delivered') {
            return back()->with('error', 'Surat Jalan ini sudah pernah dikonfirmasi diterima.');
        }

        if ($delivery->status === 'cancelled') {
            return back()->with('error', 'Surat Jalan yang dibatalkan tidak dapat dikonfirmasi.');
        }

        $request->validate([
            'received_date'          => 'required|date|after_or_equal:' . $delivery->delivery_date->toDateString(),
            'recipient_name'         => 'nullable|string|max:255',
            'receipt_notes'          => 'nullable|string',
            'auto_generate_invoice'  => 'nullable|boolean',
            'invoice_date'           => 'nullable|date',
            'due_date'               => 'nullable|date|after_or_equal:invoice_date',
        ]);

        return DB::transaction(function () use ($request, $delivery) {
            $delivery->lockForUpdate();

            $delivery->update([
                'status'         => 'delivered',
                'received_date'  => $request->received_date,
                'recipient_name' => $request->filled('recipient_name') ? $request->recipient_name : $delivery->recipient_name,
                'receipt_notes'  => $request->receipt_notes,
            ]);

            $invoiceMessage = '';
            if ($request->boolean('auto_generate_invoice') && !$delivery->is_invoiced) {
                $delivery->loadMissing(['salesOrder.customer']);
                $invoiceDate = $request->invoice_date ?? $request->received_date;
                $paymentTermDays = $delivery->salesOrder?->customer?->payment_term_days ?? 30;
                $dueDate = $request->due_date ?? \Carbon\Carbon::parse($invoiceDate)->addDays($paymentTermDays)->toDateString();

                $invoice = $this->salesInvoiceService->createFromDelivery($delivery, [
                    'invoice_date' => $invoiceDate,
                    'due_date'     => $dueDate,
                    'tax_rate'     => (float) ($delivery->salesOrder?->tax_rate ?? 0),
                    'notes'        => "Diterbitkan saat konfirmasi penerimaan Surat Jalan #{$delivery->delivery_number}",
                ], asDraft: false);
                $invoiceMessage = " dan Invoice Penjualan #{$invoice->invoice_number} langsung diterbitkan.";
            }

            return back()->with('success', "Penerimaan barang Surat Jalan #{$delivery->delivery_number} berhasil dikonfirmasi{$invoiceMessage}");
        });
    }

    public function show(Delivery $delivery): View
    {
        $delivery->load(['salesOrder.customer', 'warehouse', 'user', 'items.salesOrderItem.product', 'salesInvoice']);

        return view('sales.deliveries.show', compact('delivery'));
    }

    public function exportPdf(Delivery $delivery)
    {
        $delivery->load(['salesOrder.customer', 'warehouse', 'user', 'items.salesOrderItem.product']);
        $safeNumber = str_replace(['/', '\\'], '-', $delivery->delivery_number);
        $filename = str_starts_with($safeNumber, 'SJ-') ? $safeNumber : "SJ-{$safeNumber}";

        return $this->streamPdf('pdf.delivery', compact('delivery'), $filename);
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(Delivery::class, 'delivery_number', 'SJ');
    }
}
