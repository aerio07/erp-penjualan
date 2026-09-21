<?php

namespace App\Http\Controllers\Sales;

use App\Enums\DeliveryStatus;
use App\Enums\SalesInvoiceStatus;
use App\Enums\SalesReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Services\JournalService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Models\Customer;
use App\Traits\HasListFilters;
use App\Traits\GeneratesDocumentNumber;

class SalesReturnController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber;

    public function __construct(
        private StockService $stockService,
        private JournalService $journalService,
    ) {}

    public function index(Request $request): View
    {
        $query = SalesReturn::with(['delivery.salesOrder.customer', 'customer', 'items.product']);

        $query = $this->applySearch($query, $request, ['return_number', 'delivery.delivery_number', 'customer.name', 'reason', 'notes']);
        $query = $this->applyFilter($query, $request, 'customer_id');
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyDateRange($query, $request, 'return_date');
        $query = $this->applySort($query, $request, ['return_number', 'return_date', 'status', 'created_at'], 'return_date', 'desc');

        $perPage = (int) $request->get('per_page', 10);
        $returns   = $query->paginate($perPage)->withQueryString();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.returns.index', compact('returns', 'customers'));
    }

    public function create(Request $request): View
    {
        $selectedDeliveryId = $request->query('delivery_id');
        $selectedSoId       = $request->query('so_id');

        $deliveries = Delivery::with([
            'salesOrder.customer',
            'items.salesOrderItem.product',
            'items.salesReturnItems',
            'items.salesInvoiceItem',
            'salesInvoice.items',
            'salesInvoice.payments',
            'warehouse'
        ])
            ->where('status', DeliveryStatus::Delivered->value)
            ->orderByDesc('id')
            ->get();

        // If delivery_id is given but not so_id, resolve so_id
        if ($selectedDeliveryId && !$selectedSoId) {
            $matchedDel = $deliveries->firstWhere('id', (int) $selectedDeliveryId);
            if ($matchedDel) {
                $selectedSoId = $matchedDel->sales_order_id;
            }
        }

        // Get unique SOs that have delivered shipments
        $soIds = $deliveries->pluck('sales_order_id')->unique()->filter();
        $salesOrders = SalesOrder::with('customer')
            ->whereIn('id', $soIds)
            ->orderByDesc('id')
            ->get();

        return view('sales.returns.create', compact('deliveries', 'salesOrders', 'selectedDeliveryId', 'selectedSoId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sales_order_id'           => 'nullable|exists:sales_orders,id',
            'delivery_id'              => 'required|exists:deliveries,id',
            'return_date'              => 'required|date',
            'reason'                   => 'nullable|string',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.delivery_item_id' => 'required|exists:delivery_items,id',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.qty'              => 'required|integer|min:0',
        ]);

        $delivery = Delivery::with([
            'salesOrder',
            'items.salesOrderItem.product',
            'items.salesReturnItems',
            'items.salesInvoiceItem',
            'salesInvoice.items',
            'salesInvoice.payments',
        ])->findOrFail($request->delivery_id);

        $invoice = $delivery->salesInvoice ?? SalesInvoice::with(['items', 'payments'])->where('delivery_id', $delivery->id)->first();

        // Tolak retur jika invoice Surat Jalan masih berstatus Draft
        if ($invoice && $invoice->isDraft()) {
            $msg = "Surat Jalan terkait masih memiliki Invoice Penjualan berstatus Draft ({$invoice->invoice_number}). Silakan hapus draft invoice atau posting invoice terlebih dahulu sebelum mengajukan retur.";
            return back()->with('error', $msg)->withErrors(['items' => $msg])->withInput();
        }

        $sisaBelumBayar = $invoice ? round((float) $invoice->effective_total_amount - (float) $invoice->total_paid, 2) : null;

        // Jika invoice sudah lunas penuh (outstanding <= 0.01), tolak retur otomatis untuk mencegah overpayment
        if ($invoice && $sisaBelumBayar !== null && $sisaBelumBayar <= 0.01) {
            $msg = "Invoice {$invoice->invoice_number} sudah lunas dibayar penuh. Retur tidak dapat diproses otomatis karena akan menyebabkan kelebihan bayar. Silakan hubungi Finance untuk penanganan manual.";
            return back()->with('error', $msg)->withErrors(['items' => $msg])->withInput();
        }

        $hasAnyQty = false;
        $totalNilaiRetur = 0;

        foreach ($request->items as $itemData) {
            $qty = (int) ($itemData['qty'] ?? 0);
            if ($qty <= 0) continue;

            $hasAnyQty = true;
            $delItem = DeliveryItem::with(['salesOrderItem.product', 'salesReturnItems', 'salesInvoiceItem'])->findOrFail($itemData['delivery_item_id']);
            abort_if($delItem->delivery_id !== $delivery->id, 422, 'Item retur tidak sesuai dengan Surat Jalan yang dipilih.');
            abort_if($delItem->salesOrderItem->product_id !== (int) $itemData['product_id'], 422, 'Produk retur tidak sesuai dengan item Surat Jalan.');

            $productName = $delItem->salesOrderItem->product->name ?? 'Produk';

            // Batas 1: Batas fisik (qty terkirim - sudah diretur)
            $availablePhysical = $delItem->qty_available_for_return;
            if ($qty > $availablePhysical) {
                return back()
                    ->with('error', "Qty retur untuk '{$productName}' melebihi sisa yang bisa diretur (Terkirim: {$delItem->qty_delivered}, Sudah diretur: {$delItem->qty_returned}, Maks bisa diretur: {$availablePhysical}).")
                    ->withErrors(['items' => "Qty retur untuk '{$productName}' melebihi kuota sisa fisik yang dapat diretur."])
                    ->withInput();
            }

            // Batas 2: Batas finansial berbasis sisa tagihan invoice (jika sudah di-invoice)
            if ($invoice) {
                $maxAllowedQty = $invoice->calculateMaxReturnQty($delItem);
                if ($qty > $maxAllowedQty) {
                    $errorMsg = $maxAllowedQty === 0
                        ? "Qty retur untuk '{$productName}' tidak dapat diproses karena akan menyebabkan kelebihan bayar (sisa tagihan invoice tidak mencukupi)."
                        : "Qty retur untuk '{$productName}' maksimal {$maxAllowedQty} unit (dibatasi oleh sisa tagihan invoice yang belum dibayar).";

                    return back()->with('error', $errorMsg)->withErrors(['items' => $errorMsg])->withInput();
                }

                // Hitung akumulasi nilai retur efektif untuk validasi multi-item
                $invoiceItem = $delItem->salesInvoiceItem ?? $invoice->items->firstWhere('delivery_item_id', $delItem->id);
                $effectiveUnitPrice = ($invoiceItem && (int) $invoiceItem->qty_invoiced > 0)
                    ? ((float) $invoiceItem->subtotal + (float) $invoiceItem->tax_amount) / (int) $invoiceItem->qty_invoiced
                    : 0;

                $totalNilaiRetur += round($qty * $effectiveUnitPrice, 2);
            }
        }

        if (!$hasAnyQty) {
            return back()->with('error', 'Isi minimal satu qty barang yang akan diretur.')->withInput();
        }

        // Validasi Tambahan Agregat: Total nilai retur multi-item tidak boleh melebihi sisa belum bayar
        if ($invoice && $sisaBelumBayar !== null && $totalNilaiRetur > ($sisaBelumBayar + 0.009)) {
            $formattedTotal = number_format($totalNilaiRetur, 0, ',', '.');
            $formattedSisa  = number_format($sisaBelumBayar, 0, ',', '.');
            $multiItemMsg = "Total nilai retur (Rp {$formattedTotal}) melebihi sisa tagihan yang belum dibayar (Rp {$formattedSisa}).";

            return back()->with('error', $multiItemMsg)->withErrors(['items' => $multiItemMsg])->withInput();
        }

        DB::transaction(function () use ($request, $delivery) {
            $return = SalesReturn::create([
                'return_number' => $this->generateNumber(),
                'delivery_id'   => $delivery->id,
                'customer_id'   => $delivery->salesOrder->customer_id,
                'return_date'   => $request->return_date,
                'reason'        => $request->reason,
                'status'        => SalesReturnStatus::Draft,
                'notes'         => $request->notes,
            ]);

            foreach ($request->items as $itemData) {
                $qty = (int) ($itemData['qty'] ?? 0);
                if ($qty <= 0) continue;

                SalesReturnItem::create([
                    'sales_return_id'  => $return->id,
                    'product_id'       => $itemData['product_id'],
                    'delivery_item_id' => $itemData['delivery_item_id'],
                    'qty'              => $qty,
                    'condition'        => $itemData['condition'] ?? 'baik',
                    'reason'           => $itemData['reason'] ?? $request->reason,
                ]);
            }
        });

        return redirect()->route('sales.returns.index')
            ->with('success', 'Draft Retur Penjualan berhasil dibuat. Menunggu konfirmasi penerimaan fisik barang di gudang.');
    }

    public function show(SalesReturn $return): View
    {
        $return->load(['delivery.warehouse', 'customer', 'items.product', 'items.deliveryItem']);

        return view('sales.returns.show', compact('return'));
    }

    /**
     * Langkah 1: Gudang menerima fisik barang retur -> Catat stock movement (return_in) dan ubah status menjadi received
     */
    public function receive(SalesReturn $return): RedirectResponse
    {
        return DB::transaction(function () use ($return) {
            $return = SalesReturn::with(['delivery', 'items.product'])->lockForUpdate()->findOrFail($return->id);
            abort_if($return->status !== SalesReturnStatus::Draft, 403, 'Hanya retur berstatus draft yang dapat diterima fisiknya.');

            $delivery = $return->delivery;

            foreach ($return->items as $item) {
                $isGood = ($item->condition === 'baik');
                $movementType = $isGood ? 'return_in' : 'return_in_damaged';
                $conditionLabel = $isGood ? 'Kondisi Baik - Masuk Stok Siap Jual' : 'Kondisi Rusak - Masuk Stok Karantina';

                // Record stock movement (return_in untuk baik, return_in_damaged untuk karantina)
                $this->stockService->recordMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $delivery->warehouse_id,
                    'type'           => $movementType,
                    'quantity'       => $item->qty,
                    'unit_cost'      => $item->product->purchase_price,
                    'reference_type' => SalesReturn::class,
                    'reference_id'   => $return->id,
                    'movement_date'  => now()->toDateString(),
                    'notes'          => "Penerimaan Retur Customer [{$conditionLabel}] #{$return->return_number}",
                    'user_id'        => Auth::id(),
                ]);

                if ($isGood) {
                    $this->stockService->allocateStockToPendingDemands($item->product_id, $delivery->warehouse_id);
                }
            }

            $return->update(['status' => SalesReturnStatus::Received]);

            return back()->with('success', 'Fisik barang retur telah diterima di gudang. Barang kondisi baik masuk stok siap jual, dan barang rusak masuk stok karantina.');
        });
    }

    /**
     * Langkah 2: Finance/Admin menyelesaikan proses retur (refund/penggantian) -> Status menjadi completed + auto-journaling
     */
    public function complete(SalesReturn $return): RedirectResponse
    {
        return DB::transaction(function () use ($return) {
            $return = SalesReturn::with(['delivery', 'items.product'])->lockForUpdate()->findOrFail($return->id);
            abort_if($return->status !== SalesReturnStatus::Received, 403, 'Hanya retur yang sudah berstatus received yang dapat diselesaikan.');

            // Auto-journaling: balik piutang/PPN keluaran, serta persediaan/HPP jika kondisi baik
            $entry = $this->journalService->createFromSalesReturn($return);
            if ($entry) {
                $this->journalService->postEntry($entry);
            }

            $return->update(['status' => SalesReturnStatus::Completed]);
            $this->refreshAffectedInvoiceStatuses($return);

            return back()->with('success', 'Proses Retur Penjualan telah diselesaikan dan jurnal akuntansi otomatis diposting (jika sudah pernah di-invoice).');
        });
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(SalesReturn::class, 'return_number', 'SRET');
    }

    private function refreshAffectedInvoiceStatuses(SalesReturn $return): void
    {
        $deliveryItemIds = $return->items->pluck('delivery_item_id')->filter()->unique();

        if ($deliveryItemIds->isEmpty()) {
            return;
        }

        SalesInvoice::with(['items', 'payments'])
            ->where('status', '!=', SalesInvoiceStatus::Draft->value)
            ->whereHas('items', fn($query) => $query->whereIn('delivery_item_id', $deliveryItemIds))
            ->get()
            ->each(function (SalesInvoice $invoice) {
                $invoice->update(['status' => $this->statusFromOutstanding($invoice)]);
            });
    }

    private function statusFromOutstanding(SalesInvoice $invoice): SalesInvoiceStatus
    {
        if ($invoice->outstanding_amount <= 0.01) {
            return SalesInvoiceStatus::Paid;
        }

        return $invoice->total_paid > 0 ? SalesInvoiceStatus::Partial : SalesInvoiceStatus::Unpaid;
    }
}
