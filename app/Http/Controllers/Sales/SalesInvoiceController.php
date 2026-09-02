<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Services\JournalService;
use App\Traits\HasListFilters;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesInvoiceController extends Controller
{
    use HasListFilters;

    public function __construct(private JournalService $journalService) {}

    public function index(Request $request): View
    {
        $query = SalesInvoice::with(['salesOrder.customer', 'delivery', 'payments', 'items']);

        $query = $this->applySearch($query, $request, ['invoice_number', 'salesOrder.so_number', 'salesOrder.customer.name', 'delivery.delivery_number', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        if ($request->filled('customer_id')) {
            $query->whereHas('salesOrder', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }
        $query = $this->applyDateRange($query, $request, 'invoice_date');
        $query = $this->applySort($query, $request, ['invoice_number', 'invoice_date', 'due_date', 'total_amount', 'status', 'created_at'], 'invoice_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $invoices  = $query->paginate($perPage)->withQueryString();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.invoices.index', compact('invoices', 'customers'));
    }

    public function create(Request $request): View
    {
        // SO yang memiliki minimal 1 Surat Jalan (Delivery) belum diinvoice
        $orders = SalesOrder::with(['customer'])
            ->whereIn('status', ['confirmed', 'partially_delivered', 'done'])
            ->whereHas('deliveries', function ($q) {
                $q->where('is_invoiced', false);
            })
            ->orderByDesc('id')
            ->get();

        // Ambil SEMUA Delivery (Surat Jalan) yang belum diinvoice untuk seluruh SO yang eligible
        $availableDeliveries = Delivery::whereIn('sales_order_id', $orders->pluck('id'))
            ->where('is_invoiced', false)
            ->with([
                'items.salesOrderItem.product',
                'items.salesReturnItems.salesReturn',
                'warehouse'
            ])
            ->orderByDesc('id')
            ->get();

        $selectedSoId = $request->query('so_id');
        $selectedDeliveryId = $request->query('delivery_id');

        // Data SO lengkap untuk kalkulasi diskon header & PPN di frontend
        $ordersData = SalesOrder::with(['customer', 'items.product', 'items.deliveryItems', 'invoices.items'])
            ->whereIn('id', $orders->pluck('id'))
            ->get()
            ->map(function ($so) {
                $usedDiscount = 0;
                $usedTax = 0;
                foreach ($so->invoices as $inv) {
                    $invBaseSubtotal = $inv->items->sum(function ($it) {
                        $lineBase = $it->qty_invoiced * $it->unit_price;
                        $lineDisc = $lineBase * (($it->discount_percent ?? 0) / 100);
                        return $lineBase - $lineDisc;
                    });
                    $usedDiscount += max(0, round($invBaseSubtotal - $inv->amount, 2));
                    $usedTax += (float) $inv->tax_amount;
                }
                $so->used_header_discount = round($usedDiscount, 2);
                $so->used_tax_amount = round($usedTax, 2);
                return $so;
            })
            ->keyBy('id');

        return view('sales.invoices.create', compact('orders', 'availableDeliveries', 'selectedSoId', 'selectedDeliveryId', 'ordersData'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Auto-resolve delivery_id jika hanya ada 1 delivery belum di-invoice (backward compatibility)
        if (!$request->filled('delivery_id') && $request->filled('sales_order_id')) {
            $singleDelivery = Delivery::where('sales_order_id', $request->sales_order_id)
                ->where('is_invoiced', false)
                ->first();
            if ($singleDelivery) {
                $request->merge(['delivery_id' => $singleDelivery->id]);
            }
        }

        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'delivery_id'    => 'required|exists:deliveries,id',
            'invoice_date'   => 'required|date',
            'due_date'       => 'required|date|after_or_equal:invoice_date',
            'tax_rate'       => 'required|numeric|min:0|max:100',
            'notes'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            // Lock Delivery untuk cegah race condition
            $delivery = Delivery::with(['items.salesOrderItem.product'])
                ->lockForUpdate()
                ->findOrFail($request->delivery_id);

            // Guard: pastikan Surat Jalan ini milik SO yang dipilih
            if ((int) $delivery->sales_order_id !== (int) $request->sales_order_id) {
                return back()
                    ->with('error', 'Surat Jalan yang dipilih bukan milik Sales Order yang dipilih.')
                    ->withInput();
            }

            // Guard: pastikan Surat Jalan belum pernah dipakai untuk invoice lain
            if ($delivery->is_invoiced) {
                return back()
                    ->with('error', 'Surat Jalan ini sudah pernah digunakan untuk invoice lain. Satu Surat Jalan hanya bisa dipakai untuk satu invoice.')
                    ->withInput();
            }

            $so = SalesOrder::with(['items', 'invoices.items'])->lockForUpdate()->findOrFail($request->sales_order_id);
            $taxRate = (float) $request->tax_rate;

            // Hitung subtotal dan COGS dari item di Surat Jalan ini (qty_delivered penuh dikurangi retur pre-invoice jika ada)
            $subtotalDelivery = 0;
            $cogs = 0;
            $itemsToCreate = [];

            foreach ($delivery->items as $delItem) {
                $soItem = $delItem->salesOrderItem;
                if (!$soItem) continue;

                $returnedCompleted = (int) $delItem->salesReturnItems()
                    ->whereHas('salesReturn', fn($q) => $q->whereIn('status', ['received', 'completed']))
                    ->sum('qty');
                $qty = max(0, (int) $delItem->qty_delivered - $returnedCompleted);
                if ($qty <= 0) continue;

                $lineBase = $qty * $soItem->unit_price;
                $disc = $lineBase * ($soItem->discount_percent / 100);
                $netLine = $lineBase - $disc;
                $subtotalDelivery += $netLine;

                $itemCogs = $qty * ($soItem->product->purchase_price ?? 0);
                $cogs += $itemCogs;

                $itemsToCreate[] = [
                    'sales_order_item_id' => $soItem->id,
                    'delivery_item_id'    => $delItem->id,
                    'product_id'          => $soItem->product_id,
                    'qty_invoiced'        => $qty,
                    'unit_price'          => $soItem->unit_price,
                    'discount_percent'    => $soItem->discount_percent,
                    'discount_amount'     => $disc,
                    'subtotal'            => $netLine,
                    'tax_amount'          => 0, // dihitung setelah prorasi diskon header
                    'cogs_amount'         => $itemCogs,
                ];
            }

            if (empty($itemsToCreate)) {
                return back()
                    ->with('error', 'Surat Jalan ini tidak memiliki item yang bisa ditagih.')
                    ->withInput();
            }

            // Hitung diskon header dan PPN yang sudah diserap oleh invoice-invoice sebelumnya untuk SO ini
            $usedHeaderDiscount = 0;
            $usedTaxAmount = 0;
            $totalInvoicedSubtotal = 0;
            foreach ($so->invoices as $existingInv) {
                $invBaseSubtotal = 0;
                foreach ($existingInv->items as $invItem) {
                    $lineBase = $invItem->qty_invoiced * $invItem->unit_price;
                    $lineDisc = $lineBase * (($invItem->discount_percent ?? 0) / 100);
                    $invBaseSubtotal += ($lineBase - $lineDisc);
                }
                $totalInvoicedSubtotal += $invBaseSubtotal;
                $usedHeaderDiscount += max(0, round($invBaseSubtotal - $existingInv->amount, 2));
                $usedTaxAmount += (float) $existingInv->tax_amount;
            }

            // Cek apakah ini Surat Jalan terakhir yang belum diinvoice untuk SO ini
            $otherUninvoicedDeliveriesCount = Delivery::where('sales_order_id', $so->id)
                ->where('is_invoiced', false)
                ->where('id', '!=', $delivery->id)
                ->count();

            $totalOrderSubtotal = (float) $so->items->sum('subtotal');
            $headerDiscountAmount = (float) ($so->discount_amount ?? 0);

            $soFullyDelivered = $so->items->every(function ($item) {
                return $item->qty_delivered >= $item->qty_ordered;
            });

            $isLastInvoiceForSo = ($otherUninvoicedDeliveriesCount === 0) && (
                $soFullyDelivered ||
                in_array($so->status, ['done', 'completed']) ||
                round($subtotalDelivery + $totalInvoicedSubtotal, 2) >= round($totalOrderSubtotal, 2)
            );

            // Presisi desimal: Jika SO bernilai rupiah bulat (tanpa sen), bulatkan ke rupiah bulat (0 desimal)
            // agar tampilan di UI dan database tidak memiliki selisih 1 rupiah akibat pecahan 50 sen.
            $soTotalTax = (float) ($so->tax_amount ?? 0);
            $isIntegerRupiah = (round($soTotalTax) == $soTotalTax) && 
                               (round($totalOrderSubtotal) == $totalOrderSubtotal) && 
                               (round($headerDiscountAmount) == $headerDiscountAmount);
            $dec = $isIntegerRupiah ? 0 : 2;

            // Alokasi diskon header:
            // Jika invoice terakhir: ambil SELURUH sisa diskon header agar totalnya presisi 100% tanpa selisih pembulatan.
            if ($isLastInvoiceForSo && $headerDiscountAmount > 0) {
                $proratedHeaderDiscount = max(0, round($headerDiscountAmount - $usedHeaderDiscount, $dec));
            } else {
                $proratedHeaderDiscount = $totalOrderSubtotal > 0
                    ? round(($subtotalDelivery / $totalOrderSubtotal) * $headerDiscountAmount, $dec)
                    : 0;
            }

            $dpp = max(0, round($subtotalDelivery - $proratedHeaderDiscount, $dec));

            // Alokasi PPN:
            // Jika invoice terakhir dan tarif PPN sama dengan SO: ambil SELURUH sisa PPN dari SO agar totalnya presisi 100% tanpa selisih pembulatan.
            if ($isLastInvoiceForSo && abs((float) $so->tax_rate - $taxRate) < 0.001 && $soTotalTax > 0) {
                $taxAmount = max(0, round($soTotalTax - $usedTaxAmount, $dec));
            } else {
                $taxAmount = round($dpp * ($taxRate / 100), $dec);
            }

            $totalAmount = round($dpp + $taxAmount, $dec);

            $invoice = SalesInvoice::create([
                'invoice_number' => $this->generateNumber(),
                'sales_order_id' => $so->id,
                'delivery_id'    => $delivery->id,
                'amount'         => $dpp,
                'tax_rate'       => $taxRate,
                'tax_amount'     => $taxAmount,
                'total_amount'   => $totalAmount,
                'invoice_date'   => $request->invoice_date,
                'due_date'       => $request->due_date,
                'status'         => 'unpaid',
                'notes'          => $request->notes,
            ]);

            // Hitung faktor prorasi diskon header untuk setiap baris item
            $discountRatio = ($subtotalDelivery > 0 && $proratedHeaderDiscount > 0)
                ? ($proratedHeaderDiscount / $subtotalDelivery)
                : 0;

            // Simpan item invoice dengan prorasi diskon header dan PPN per baris
            // Baris item terakhir mengambil sisa pembulatan agar sum(subtotal) == DPP dan sum(tax) == taxAmount
            $allocatedHeaderDiscount = 0;
            $allocatedSubtotal = 0;
            $allocatedTax = 0;
            $itemCount = count($itemsToCreate);

            foreach ($itemsToCreate as $idx => $itemData) {
                $itemData['sales_invoice_id'] = $invoice->id;
                $isLastItem = ($idx === $itemCount - 1);

                if ($isLastItem) {
                    $itemHeaderDiscount = max(0, round($proratedHeaderDiscount - $allocatedHeaderDiscount, $dec));
                    $itemNetSubtotal    = max(0, round($dpp - $allocatedSubtotal, $dec));
                    $itemTax            = max(0, round($taxAmount - $allocatedTax, $dec));
                } else {
                    $itemHeaderDiscount = round($itemData['subtotal'] * $discountRatio, $dec);
                    $itemNetSubtotal    = max(0, round($itemData['subtotal'] - $itemHeaderDiscount, $dec));
                    $itemTax            = round($itemNetSubtotal * ($taxRate / 100), $dec);

                    $allocatedHeaderDiscount += $itemHeaderDiscount;
                    $allocatedSubtotal       += $itemNetSubtotal;
                    $allocatedTax            += $itemTax;
                }

                $itemData['discount_amount'] = round($itemData['discount_amount'] + $itemHeaderDiscount, $dec);
                $itemData['subtotal']        = $itemNetSubtotal;
                $itemData['tax_amount']      = $itemTax;

                SalesInvoiceItem::create($itemData);
            }

            // Tandai Delivery (Surat Jalan) ini sudah di-invoice (1 SJ = 1 Invoice)
            $delivery->update([
                'is_invoiced'      => true,
                'sales_invoice_id' => $invoice->id,
            ]);

            // Update invoiced_qty pada delivery_items untuk integritas data historis
            foreach ($delivery->items as $delItem) {
                $delItem->update(['invoiced_qty' => $delItem->qty_delivered]);
            }

            // Automatic Journal Entry (Piutang -> Penjualan, PPN Keluaran, serta HPP -> Persediaan)
            $entry = $this->journalService->createFromSalesInvoice($invoice, $cogs);
            $this->journalService->postEntry($entry);

            return redirect()->route('sales.invoices.index')
                ->with('success', "Invoice Penjualan #{$invoice->invoice_number} berhasil diterbitkan dari Surat Jalan #{$delivery->delivery_number} dan Jurnal Akuntansi otomatis diposting.");
        });
    }

    public function show(SalesInvoice $invoice): View
    {
        $invoice->load(['salesOrder.customer', 'delivery.warehouse', 'items.product', 'items.deliveryItem.delivery', 'payments']);

        return view('sales.invoices.show', compact('invoice'));
    }

    public function updateTaxInvoice(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $request->validate([
            'tax_invoice_number' => 'nullable|string|max:50',
        ], [
            'tax_invoice_number.max' => 'Nomor Faktur Pajak maksimal 50 karakter.',
        ]);

        $invoice->update([
            'tax_invoice_number' => $request->filled('tax_invoice_number') ? trim($request->tax_invoice_number) : null,
        ]);

        return back()->with('success', 'Nomor Faktur Pajak berhasil diperbarui.');
    }

    public function exportPdf(SalesInvoice $invoice)
    {
        $invoice->load(['salesOrder.customer', 'delivery.warehouse', 'items.product', 'items.deliveryItem.delivery', 'payments']);
        $pdf = Pdf::loadView('pdf.sales-invoice', compact('invoice'));

        return $pdf->download("SINV-{$invoice->invoice_number}.pdf");
    }

    private function generateNumber(): string
    {
        $prefix = 'SINV-' . date('Ym') . '-';
        $last   = SalesInvoice::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('invoice_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
