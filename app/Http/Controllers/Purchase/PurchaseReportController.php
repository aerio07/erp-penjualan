<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Traits\HasListFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseReportController extends Controller
{
    use HasListFilters;

    /**
     * Laporan Monitoring Fulfillment Purchase Order (PO -> LPB -> Retur -> Invoice -> Pembayaran)
     */
    public function fulfillment(Request $request): View
    {
        $query = PurchaseOrder::query()
            ->with([
                'supplier',
                'items',
                'goodsReceipts.items',
                'invoices.goodsReceipt',
                'invoices.payments',
                'invoices.items',
                'returns.goodsReceipt',
                'returns.items.product',
            ])
            ->select('purchase_orders.*')
            ->selectRaw('(SELECT COALESCE(SUM(qty_ordered), 0) FROM purchase_order_items WHERE purchase_order_items.purchase_order_id = purchase_orders.id) as qty_ordered_sum')
            ->selectRaw('(SELECT COALESCE(SUM(gri.qty_received), 0) FROM goods_receipt_items gri JOIN goods_receipts gr ON gr.id = gri.goods_receipt_id WHERE gr.purchase_order_id = purchase_orders.id) as qty_received_sum')
            ->selectRaw('(SELECT COALESCE(SUM(pri.qty), 0) FROM purchase_return_items pri JOIN purchase_returns pr ON pr.id = pri.purchase_return_id JOIN goods_receipts gr ON gr.id = pr.goods_receipt_id WHERE gr.purchase_order_id = purchase_orders.id AND pr.status = "completed" AND pri.source_type = "accepted") as qty_returned_sum')
            ->selectRaw('(SELECT COALESCE(SUM(pii.qty_invoiced), 0) FROM purchase_invoice_items pii JOIN purchase_invoices pi ON pi.id = pii.purchase_invoice_id WHERE pi.purchase_order_id = purchase_orders.id) as qty_invoiced_sum')
            ->selectRaw('(SELECT COALESCE(SUM(pii.reversed_qty), 0) FROM purchase_invoice_items pii JOIN purchase_invoices pi ON pi.id = pii.purchase_invoice_id WHERE pi.purchase_order_id = purchase_orders.id) as qty_reversed_sum')
            ->selectRaw('(SELECT COALESCE(SUM(pi.total_amount), 0) FROM purchase_invoices pi WHERE pi.purchase_order_id = purchase_orders.id) as total_invoice_sum')
            ->selectRaw('(SELECT COALESCE(SUM(CASE WHEN pii.qty_invoiced > 0 THEN ((pii.subtotal + pii.tax_amount) / pii.qty_invoiced) * LEAST(pii.reversed_qty, pii.qty_invoiced) ELSE 0 END), 0) FROM purchase_invoice_items pii JOIN purchase_invoices pi ON pi.id = pii.purchase_invoice_id WHERE pi.purchase_order_id = purchase_orders.id) as total_reversed_amount_sum')
            ->selectRaw('(SELECT COALESCE(SUM(pp.amount), 0) FROM purchase_payments pp JOIN purchase_invoices pi ON pi.id = pp.purchase_invoice_id WHERE pi.purchase_order_id = purchase_orders.id) as total_paid_sum');

        // Filter: supplier_id, status, rentang tanggal
        $query = $this->applyFilter($query, $request, 'supplier_id');
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyDateRange($query, $request, 'order_date', 'date_from', 'date_to');
        $query = $this->applySearch($query, $request, ['po_number', 'supplier.name']);

        // Filter: status pembayaran
        if ($request->filled('payment_status')) {
            $ps = $request->payment_status;
            if ($ps === 'no_invoice') {
                $query->having('total_invoice_sum', '<=', 0);
            } elseif ($ps === 'unpaid') {
                $query->havingRaw('total_invoice_sum > total_reversed_amount_sum AND total_paid_sum <= 0');
            } elseif ($ps === 'partial') {
                $query->havingRaw('total_invoice_sum > total_reversed_amount_sum AND total_paid_sum > 0 AND total_paid_sum < (total_invoice_sum - total_reversed_amount_sum)');
            } elseif ($ps === 'paid') {
                $query->havingRaw('(total_invoice_sum > 0 AND (total_invoice_sum - total_reversed_amount_sum) <= 0) OR (total_invoice_sum > 0 AND total_paid_sum >= (total_invoice_sum - total_reversed_amount_sum))');
            }
        }

        // Filter: status penerimaan & penagihan
        if ($request->filled('billing_status')) {
            $bs = $request->billing_status;
            if ($bs === 'unbilled') {
                $query->havingRaw('(qty_received_sum - qty_returned_sum) > 0 AND (qty_invoiced_sum - qty_reversed_sum) = 0');
            } elseif ($bs === 'partial_billed') {
                $query->havingRaw('(qty_invoiced_sum - qty_reversed_sum) > 0 AND (qty_invoiced_sum - qty_reversed_sum) < (qty_received_sum - qty_returned_sum)');
            } elseif ($bs === 'fully_billed') {
                $query->havingRaw('(qty_received_sum - qty_returned_sum) > 0 AND (qty_invoiced_sum - qty_reversed_sum) >= (qty_received_sum - qty_returned_sum)');
            }
        }

        // Default sort: order_date desc
        $query = $this->applySort($query, $request, ['order_date', 'po_number', 'total_amount', 'created_at'], 'order_date', 'desc');

        $orders = $query->paginate($request->get('per_page', 20))->withQueryString();

        // Mutasi & Dekorasi data untuk kebutuhan UI
        $orders->getCollection()->transform(function ($order) {
            $totalInvGross = (float) $order->total_invoice_sum;
            $totalReversed = (float) $order->total_reversed_amount_sum;
            $totalInvEffective = max(0, round($totalInvGross - $totalReversed, 2));
            $totalPaid = (float) $order->total_paid_sum;

            $order->effective_total_invoice = $totalInvEffective;
            $order->remaining_balance = max(0, round($totalInvEffective - $totalPaid, 2));

            // Net Qty (setelah dikurangi retur fisik dan reversed invoice)
            $qtyOrdered = (int) $order->qty_ordered_sum;
            $order->net_qty_received = max(0, (int) $order->qty_received_sum - (int) $order->qty_returned_sum);
            $order->net_qty_invoiced = max(0, (int) $order->qty_invoiced_sum - (int) $order->qty_reversed_sum);

            // Status Bayar
            if ($totalInvGross <= 0) {
                $order->payment_status_label = 'Belum Ada Invoice';
                $order->payment_status_badge = 'secondary';
            } elseif ($totalInvEffective <= 0) {
                $order->payment_status_label = 'Lunas (Diretur Penuh)';
                $order->payment_status_badge = 'done';
            } elseif ($totalPaid <= 0) {
                $order->payment_status_label = 'Belum Dibayar';
                $order->payment_status_badge = 'danger';
            } elseif ($totalPaid < $totalInvEffective) {
                $order->payment_status_label = 'Sebagian';
                $order->payment_status_badge = 'warning';
            } else {
                $order->payment_status_label = 'Lunas';
                $order->payment_status_badge = 'done';
            }

            // Status Penerimaan LPB Net
            $order->receipt_progress_percent = $qtyOrdered > 0 ? min(100, round(($order->net_qty_received / $qtyOrdered) * 100)) : 0;

            // Status Penagihan Net
            $order->invoice_progress_percent = $order->net_qty_received > 0 ? min(100, round(($order->net_qty_invoiced / $order->net_qty_received) * 100)) : 0;

            return $order;
        });

        // Metrik Ringkasan (Executive Summary KPI Cards)
        $kpiQuery = PurchaseOrder::query();
        if ($request->filled('supplier_id')) $kpiQuery->where('supplier_id', $request->supplier_id);
        if ($request->filled('status')) $kpiQuery->where('status', $request->status);
        if ($request->filled('date_from')) $kpiQuery->whereDate('order_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $kpiQuery->whereDate('order_date', '<=', $request->date_to);

        $totalOrdersCount = (clone $kpiQuery)->count();
        $totalOrdersAmount = (clone $kpiQuery)->sum('total_amount');

        // Akumulasi invoice dan payments untuk filter saat ini (menggunakan effective_total_amount)
        $filteredPoIds = (clone $kpiQuery)->pluck('id');
        $filteredInvoices = PurchaseInvoice::with('items')->whereIn('purchase_order_id', $filteredPoIds)->get();
        $totalInvoicedAmount = (float) $filteredInvoices->sum('effective_total_amount');
        $totalPaidAmount = (float) PurchasePayment::whereHas('purchaseInvoice', fn($q) => $q->whereIn('purchase_order_id', $filteredPoIds))->sum('amount');
        $totalOutstandingAmount = max(0, round($totalInvoicedAmount - $totalPaidAmount, 2));

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('purchase.reports.fulfillment', compact(
            'orders',
            'suppliers',
            'totalOrdersCount',
            'totalOrdersAmount',
            'totalInvoicedAmount',
            'totalPaidAmount',
            'totalOutstandingAmount'
        ));
    }

    /**
     * Rekap Pembelian per Barang
     */
    public function recapByProduct(Request $request): View
    {
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');
        $categoryId = $request->input('category_id');
        $search     = $request->input('q');

        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        $query = Product::with('category')
            ->where('is_active', true)
            ->withSum(['purchaseInvoiceItems as total_qty' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->whereHas('purchaseInvoice', fn($pi) => $pi->where('invoice_date', '>=', $dateFrom));
                if ($dateTo)   $q->whereHas('purchaseInvoice', fn($pi) => $pi->where('invoice_date', '<=', $dateTo));
            }], 'qty_invoiced')
            ->withSum(['purchaseInvoiceItems as total_amount' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->whereHas('purchaseInvoice', fn($pi) => $pi->where('invoice_date', '>=', $dateFrom));
                if ($dateTo)   $q->whereHas('purchaseInvoice', fn($pi) => $pi->where('invoice_date', '<=', $dateTo));
            }], 'subtotal')
            ->withCount(['purchaseInvoiceItems as transaction_count' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->whereHas('purchaseInvoice', fn($pi) => $pi->where('invoice_date', '>=', $dateFrom));
                if ($dateTo)   $q->whereHas('purchaseInvoice', fn($pi) => $pi->where('invoice_date', '<=', $dateTo));
            }]);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderByDesc('total_amount')->paginate(20)->withQueryString();

        $products->getCollection()->transform(function ($p) {
            $p->avg_price = $p->total_qty > 0 ? round($p->total_amount / $p->total_qty, 2) : $p->purchase_price;
            return $p;
        });

        // Metrik Ringkasan (KPI Cards)
        $summaryQuery = Product::query();
        if ($categoryId) $summaryQuery->where('category_id', $categoryId);
        if ($search) {
            $summaryQuery->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }
        $totalItemsPurchased = (float) $summaryQuery->withSum(['purchaseInvoiceItems as total_qty' => function ($q) use ($dateFrom, $dateTo) {
            if ($dateFrom) $q->whereHas('purchaseInvoice', fn($pi) => $pi->where('invoice_date', '>=', $dateFrom));
            if ($dateTo)   $q->whereHas('purchaseInvoice', fn($pi) => $pi->where('invoice_date', '<=', $dateTo));
        }], 'qty_invoiced')->get()->sum('total_qty');

        $totalSpend = (float) $summaryQuery->withSum(['purchaseInvoiceItems as total_amt' => function ($q) use ($dateFrom, $dateTo) {
            if ($dateFrom) $q->whereHas('purchaseInvoice', fn($pi) => $pi->where('invoice_date', '>=', $dateFrom));
            if ($dateTo)   $q->whereHas('purchaseInvoice', fn($pi) => $pi->where('invoice_date', '<=', $dateTo));
        }], 'subtotal')->get()->sum('total_amt');

        return view('purchase.reports.recap-by-product', compact(
            'products', 'categories', 'totalItemsPurchased', 'totalSpend', 'dateFrom', 'dateTo', 'categoryId', 'search'
        ));
    }
}
