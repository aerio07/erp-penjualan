<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryItem;
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

class SalesReturnController extends Controller
{
    use HasListFilters;

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

        $perPage = (int) $request->get('per_page', 20);
        $returns   = $query->paginate($perPage)->withQueryString();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.returns.index', compact('returns', 'customers'));
    }

    public function create(Request $request): View
    {
        $selectedDeliveryId = $request->query('delivery_id');
        $deliveries = Delivery::with(['salesOrder.customer', 'items.salesOrderItem.product', 'items.salesReturnItems', 'warehouse'])
            ->orderByDesc('id')
            ->get();

        return view('sales.returns.create', compact('deliveries', 'selectedDeliveryId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'delivery_id'              => 'required|exists:deliveries,id',
            'return_date'              => 'required|date',
            'reason'                   => 'nullable|string',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.delivery_item_id' => 'required|exists:delivery_items,id',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.qty'              => 'required|integer|min:0',
        ]);

        $delivery = Delivery::with(['salesOrder', 'items.salesReturnItems'])->findOrFail($request->delivery_id);

        $hasAnyQty = false;
        foreach ($request->items as $itemData) {
            $qty = (int) ($itemData['qty'] ?? 0);
            if ($qty <= 0) continue;

            $hasAnyQty = true;
            $delItem = DeliveryItem::with(['salesOrderItem.product', 'salesReturnItems'])->findOrFail($itemData['delivery_item_id']);
            abort_if($delItem->delivery_id !== $delivery->id, 422, 'Item retur tidak sesuai dengan Surat Jalan yang dipilih.');
            abort_if($delItem->salesOrderItem->product_id !== (int) $itemData['product_id'], 422, 'Produk retur tidak sesuai dengan item Surat Jalan.');

            $available = $delItem->qty_available_for_return;
            if ($qty > $available) {
                return back()
                    ->with('error', "Qty retur untuk '{$delItem->salesOrderItem->product->name}' melebihi sisa yang bisa diretur (Terkirim: {$delItem->qty_delivered}, Sudah diretur: {$delItem->qty_returned}, Maks bisa diretur: {$available}).")
                    ->withInput();
            }
        }

        if (!$hasAnyQty) {
            return back()->with('error', 'Isi minimal satu qty barang yang akan diretur.')->withInput();
        }

        DB::transaction(function () use ($request, $delivery) {
            $return = SalesReturn::create([
                'return_number' => $this->generateNumber(),
                'delivery_id'   => $delivery->id,
                'customer_id'   => $delivery->salesOrder->customer_id,
                'return_date'   => $request->return_date,
                'reason'        => $request->reason,
                'status'        => 'draft',
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
        abort_if($return->status !== 'draft', 403, 'Hanya retur berstatus draft yang dapat diterima fisiknya.');

        DB::transaction(function () use ($return) {
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
            }

            $return->update(['status' => 'received']);
        });

        return back()->with('success', 'Fisik barang retur telah diterima di gudang. Barang kondisi baik masuk stok siap jual, dan barang rusak masuk stok karantina.');
    }

    /**
     * Langkah 2: Finance/Admin menyelesaikan proses retur (refund/penggantian) -> Status menjadi completed + auto-journaling
     */
    public function complete(SalesReturn $return): RedirectResponse
    {
        abort_if($return->status !== 'received', 403, 'Hanya retur yang sudah berstatus received yang dapat diselesaikan.');

        DB::transaction(function () use ($return) {
            // Auto-journaling: balik piutang/PPN keluaran, serta persediaan/HPP jika kondisi baik
            $entry = $this->journalService->createFromSalesReturn($return);
            if ($entry) {
                $this->journalService->postEntry($entry);
            }

            $return->update(['status' => 'completed']);
        });

        return back()->with('success', 'Proses Retur Penjualan telah diselesaikan dan jurnal akuntansi otomatis diposting (jika sudah pernah di-invoice).');
    }

    private function generateNumber(): string
    {
        $prefix = 'SRET-' . date('Ym') . '-';
        $last   = SalesReturn::where('return_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('return_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}

