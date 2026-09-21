<?php

namespace App\Http\Controllers\Sales;

use App\Enums\DeliveryStatus;
use App\Enums\SalesInvoiceStatus;
use App\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Services\JournalService;
use App\Traits\HasListFilters;
use App\Traits\GeneratesDocumentNumber;
use App\Traits\ExportsPdfDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesInvoiceController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber, ExportsPdfDocument;

    public function __construct(
        private JournalService $journalService,
        private \App\Services\SalesInvoiceService $salesInvoiceService
    ) {}

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

        $perPage = (int) $request->get('per_page', 10);
        $invoices  = $query->paginate($perPage)->withQueryString();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.invoices.index', compact('invoices', 'customers'));
    }

    public function create(Request $request): View
    {
        // SO yang memiliki minimal 1 Surat Jalan (Delivery) yang SUDAH DITERIMA (delivered) dan belum diinvoice
        $orders = SalesOrder::with(['customer'])
            ->whereIn('status', [
                SalesOrderStatus::Confirmed->value,
                SalesOrderStatus::PartiallyDelivered->value,
                SalesOrderStatus::Done->value,
            ])
            ->whereHas('deliveries', function ($q) {
                $q->where('is_invoiced', false)->where('status', DeliveryStatus::Delivered->value);
            })
            ->orderByDesc('id')
            ->get();

        // Ambil SEMUA Delivery (Surat Jalan) yang sudah diterima dan belum diinvoice untuk seluruh SO yang eligible
        $availableDeliveries = Delivery::whereIn('sales_order_id', $orders->pluck('id'))
            ->where('is_invoiced', false)
            ->where('status', DeliveryStatus::Delivered->value)
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
        // Auto-resolve delivery_id jika hanya ada 1 delivery yang belum di-invoice
        if (!$request->filled('delivery_id') && $request->filled('sales_order_id')) {
            $singleDelivery = Delivery::where('sales_order_id', $request->sales_order_id)
                ->where('is_invoiced', false)
                ->first();
            if ($singleDelivery) {
                if ($singleDelivery->status !== DeliveryStatus::Delivered) {
                    $singleDelivery->update(['status' => DeliveryStatus::Delivered]);
                }
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

        try {
            return DB::transaction(function () use ($request) {
                // Lock Delivery untuk cegah race condition
                $delivery = Delivery::with(['items.salesOrderItem.product'])
                    ->lockForUpdate()
                    ->findOrFail($request->delivery_id);

                // Guard: pastikan Surat Jalan ini milik SO yang dipilih
                if ((int) $delivery->sales_order_id !== (int) $request->sales_order_id) {
                    throw new \InvalidArgumentException('Surat Jalan yang dipilih bukan milik Sales Order yang dipilih.');
                }

                // Guard: pastikan Surat Jalan belum pernah dipakai untuk invoice lain
                if ($delivery->is_invoiced) {
                    throw new \InvalidArgumentException('Surat Jalan ini sudah pernah digunakan untuk invoice lain. Satu Surat Jalan hanya bisa dipakai untuk satu invoice.');
                }

                // Guard: pastikan Surat Jalan sudah berstatus delivered (sudah diterima)
                if ($delivery->status !== DeliveryStatus::Delivered) {
                    throw new \InvalidArgumentException("Surat Jalan #{$delivery->delivery_number} masih dalam pengiriman (In Transit). Barang harus dikonfirmasi telah diterima pembeli sebelum invoice diterbitkan.");
                }

                $invoice = $this->salesInvoiceService->createFromDelivery($delivery, [
                    'invoice_date' => $request->invoice_date,
                    'due_date'     => $request->due_date,
                    'tax_rate'     => $request->tax_rate,
                    'notes'        => $request->notes,
                ]);

                return redirect()->route('sales.invoices.index')
                    ->with('success', "Invoice Penjualan #{$invoice->invoice_number} berhasil disimpan sebagai Draft.");
            });
        } catch (\InvalidArgumentException|\App\Exceptions\PeriodClosedException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(SalesInvoice $invoice): View
    {
        $invoice->load(['salesOrder.customer', 'delivery.warehouse', 'items.product', 'items.deliveryItem.delivery', 'payments']);

        return view('sales.invoices.show', compact('invoice'));
    }

    public function edit(SalesInvoice $invoice): View|RedirectResponse
    {
        if (!$invoice->isDraft()) {
            return redirect()->route('sales.invoices.show', $invoice)
                ->with('error', 'Hanya invoice berstatus Draft yang dapat diedit.');
        }

        $invoice->load(['salesOrder.customer', 'delivery.warehouse', 'items.product', 'items.deliveryItem']);

        return view('sales.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        if (!$invoice->isDraft()) {
            return redirect()->route('sales.invoices.show', $invoice)
                ->with('error', 'Hanya invoice berstatus Draft yang dapat diedit.');
        }

        $request->validate([
            'tax_invoice_number' => 'nullable|string|max:50',
            'invoice_date'       => 'required|date',
            'due_date'           => 'required|date|after_or_equal:invoice_date',
            'tax_rate'           => 'required|numeric|min:0|max:100',
            'notes'              => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($request, $invoice) {
                $taxRate = (float) $request->tax_rate;
                $dpp = (float) $invoice->amount;

                $isIntegerRupiah = (round($invoice->total_amount) == $invoice->total_amount);
                $dec = $isIntegerRupiah ? 0 : 2;
                $taxAmount = round($dpp * ($taxRate / 100), $dec);
                $totalAmount = round($dpp + $taxAmount, $dec);

                $invoice->update([
                    'tax_invoice_number' => $request->filled('tax_invoice_number') ? trim($request->tax_invoice_number) : null,
                    'invoice_date'       => $request->invoice_date,
                    'due_date'           => $request->due_date,
                    'tax_rate'           => $taxRate,
                    'tax_amount'         => $taxAmount,
                    'total_amount'       => $totalAmount,
                    'notes'              => $request->notes,
                ]);

                // Update tax pada item jika tax_rate berubah
                $items = $invoice->items;
                $allocatedTax = 0;
                $itemCount = $items->count();
                foreach ($items as $idx => $item) {
                    $isLast = ($idx === $itemCount - 1);
                    $itemTax = $isLast ? max(0, round($taxAmount - $allocatedTax, $dec)) : round($item->subtotal * ($taxRate / 100), $dec);
                    $allocatedTax += $itemTax;
                    $item->update(['tax_amount' => $itemTax]);
                }

                return redirect()->route('sales.invoices.show', $invoice)
                    ->with('success', "Draft Invoice Penjualan #{$invoice->invoice_number} berhasil diperbarui.");
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui draft invoice: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(SalesInvoice $invoice): RedirectResponse
    {
        if (!$invoice->isDraft()) {
            return redirect()->route('sales.invoices.show', $invoice)
                ->with('error', 'Hanya invoice berstatus Draft yang dapat dihapus.');
        }

        try {
            return DB::transaction(function () use ($invoice) {
                $invoiceNumber = $invoice->invoice_number;

                // Lepaskan kaitan Surat Jalan (Delivery)
                if ($invoice->delivery_id) {
                    $del = Delivery::find($invoice->delivery_id);
                    if ($del) {
                        $del->update([
                            'is_invoiced'      => false,
                            'sales_invoice_id' => null,
                        ]);
                        foreach ($del->items as $delItem) {
                            $delItem->update(['invoiced_qty' => 0]);
                        }
                    }
                }
                Delivery::where('sales_invoice_id', $invoice->id)->update([
                    'is_invoiced'      => false,
                    'sales_invoice_id' => null,
                ]);

                // Hapus items lalu invoice
                $invoice->items()->delete();
                $invoice->delete();

                return redirect()->route('sales.invoices.index')
                    ->with('success', "Draft Invoice Penjualan #{$invoiceNumber} berhasil dihapus dan Surat Jalan terkait telah dilepaskan.");
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus draft invoice: ' . $e->getMessage());
        }
    }

    public function post(SalesInvoice $invoice): RedirectResponse
    {
        if (!$invoice->isDraft()) {
            return redirect()->route('sales.invoices.show', $invoice)
                ->with('error', 'Hanya invoice berstatus Draft yang dapat diposting.');
        }

        try {
            return DB::transaction(function () use ($invoice) {
                $invoice = SalesInvoice::lockForUpdate()->findOrFail($invoice->id);
                if (!$invoice->isDraft()) {
                    throw new \InvalidArgumentException('Invoice ini sudah pernah diposting.');
                }

                $this->salesInvoiceService->postInvoice($invoice);

                return redirect()->route('sales.invoices.index')
                    ->with('success', "Invoice Penjualan #{$invoice->invoice_number} berhasil diposting dan Jurnal Akuntansi otomatis diterbitkan.");
            });
        } catch (\InvalidArgumentException|\App\Exceptions\PeriodClosedException $e) {
            return back()->with('error', $e->getMessage());
        }
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
        abort_if(!$invoice->isPosted(), 403, 'PDF hanya dapat diunduh setelah Faktur Penjualan diposting.');

        $invoice->load(['salesOrder.customer', 'delivery.warehouse', 'items.product', 'items.deliveryItem.delivery', 'payments']);
        $safeNumber = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        $filename = str_starts_with($safeNumber, 'SINV-') ? $safeNumber : "SINV-{$safeNumber}";

        return $this->streamPdf('pdf.sales-invoice', compact('invoice'), $filename);
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(SalesInvoice::class, 'invoice_number', 'SINV');
    }
}
