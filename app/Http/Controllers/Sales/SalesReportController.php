<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesPayment;
use App\Traits\HasListFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesReportController extends Controller
{
    use HasListFilters;

    /**
     * Laporan Monitoring Fulfillment Sales Order (SO -> SJ -> Retur -> Invoice -> Pembayaran)
     */
    public function fulfillment(Request $request): View
    {
        $query = SalesOrder::query()
            ->with([
                'customer',
                'items',
                'deliveries.items',
                'invoices.delivery',
                'invoices.payments',
                'invoices.items',
                'returns.delivery',
                'returns.items.product',
            ])
            ->select('sales_orders.*')
            ->selectRaw('(SELECT COALESCE(SUM(qty_ordered), 0) FROM sales_order_items WHERE sales_order_items.sales_order_id = sales_orders.id) as qty_ordered_sum')
            ->selectRaw('(SELECT COALESCE(SUM(di.qty_delivered), 0) FROM delivery_items di JOIN deliveries d ON d.id = di.delivery_id WHERE d.sales_order_id = sales_orders.id) as qty_delivered_sum')
            ->selectRaw('(SELECT COALESCE(SUM(sri.qty), 0) FROM sales_return_items sri JOIN sales_returns sr ON sr.id = sri.sales_return_id JOIN deliveries d ON d.id = sr.delivery_id WHERE d.sales_order_id = sales_orders.id AND sr.status IN ("received", "completed")) as qty_returned_sum')
            ->selectRaw('(SELECT COALESCE(SUM(sii.qty_invoiced), 0) FROM sales_invoice_items sii JOIN sales_invoices si ON si.id = sii.sales_invoice_id WHERE si.sales_order_id = sales_orders.id) as qty_invoiced_sum')
            ->selectRaw('(SELECT COALESCE(SUM(sii.reversed_qty), 0) FROM sales_invoice_items sii JOIN sales_invoices si ON si.id = sii.sales_invoice_id WHERE si.sales_order_id = sales_orders.id) as qty_reversed_sum')
            ->selectRaw('(SELECT COALESCE(SUM(si.total_amount), 0) FROM sales_invoices si WHERE si.sales_order_id = sales_orders.id) as total_invoice_sum')
            ->selectRaw('(SELECT COALESCE(SUM(CASE WHEN sii.qty_invoiced > 0 THEN ((sii.subtotal + sii.tax_amount) / sii.qty_invoiced) * LEAST(sii.reversed_qty, sii.qty_invoiced) ELSE 0 END), 0) FROM sales_invoice_items sii JOIN sales_invoices si ON si.id = sii.sales_invoice_id WHERE si.sales_order_id = sales_orders.id) as total_reversed_amount_sum')
            ->selectRaw('(SELECT COALESCE(SUM(sp.amount), 0) FROM sales_payments sp JOIN sales_invoices si ON si.id = sp.sales_invoice_id WHERE si.sales_order_id = sales_orders.id) as total_paid_sum');

        // Filter: customer_id, status, rentang tanggal
        $query = $this->applyFilter($query, $request, 'customer_id');
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyDateRange($query, $request, 'order_date', 'date_from', 'date_to');
        $query = $this->applySearch($query, $request, ['so_number', 'customer.name']);

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

        // Filter: status fulfillment / penagihan
        if ($request->filled('billing_status')) {
            $bs = $request->billing_status;
            if ($bs === 'unbilled') {
                $query->havingRaw('(qty_delivered_sum - qty_returned_sum) > 0 AND (qty_invoiced_sum - qty_reversed_sum) = 0');
            } elseif ($bs === 'partial_billed') {
                $query->havingRaw('(qty_invoiced_sum - qty_reversed_sum) > 0 AND (qty_invoiced_sum - qty_reversed_sum) < (qty_delivered_sum - qty_returned_sum)');
            } elseif ($bs === 'fully_billed') {
                $query->havingRaw('(qty_delivered_sum - qty_returned_sum) > 0 AND (qty_invoiced_sum - qty_reversed_sum) >= (qty_delivered_sum - qty_returned_sum)');
            }
        }

        // Default sort: order_date desc
        $query = $this->applySort($query, $request, ['order_date', 'so_number', 'total_amount', 'created_at'], 'order_date', 'desc');

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
            $order->net_qty_delivered = max(0, (int) $order->qty_delivered_sum - (int) $order->qty_returned_sum);
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

            // Status Pengiriman Net
            $order->delivery_progress_percent = $qtyOrdered > 0 ? min(100, round(($order->net_qty_delivered / $qtyOrdered) * 100)) : 0;

            // Status Penagihan Net
            $order->invoice_progress_percent = $order->net_qty_delivered > 0 ? min(100, round(($order->net_qty_invoiced / $order->net_qty_delivered) * 100)) : 0;

            return $order;
        });

        // Metrik Ringkasan (Executive Summary KPI Cards)
        $kpiQuery = SalesOrder::query();
        if ($request->filled('customer_id')) $kpiQuery->where('customer_id', $request->customer_id);
        if ($request->filled('status')) $kpiQuery->where('status', $request->status);
        if ($request->filled('date_from')) $kpiQuery->whereDate('order_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $kpiQuery->whereDate('order_date', '<=', $request->date_to);

        $totalOrdersCount = (clone $kpiQuery)->count();
        $totalOrdersAmount = (clone $kpiQuery)->sum('total_amount');

        // Akumulasi invoice dan payments untuk filter saat ini (menggunakan effective_total_amount)
        $filteredSoIds = (clone $kpiQuery)->pluck('id');
        $filteredInvoices = SalesInvoice::with('items')->whereIn('sales_order_id', $filteredSoIds)->get();
        $totalInvoicedAmount = (float) $filteredInvoices->sum('effective_total_amount');
        $totalPaidAmount = (float) SalesPayment::whereHas('salesInvoice', fn($q) => $q->whereIn('sales_order_id', $filteredSoIds))->sum('amount');
        $totalOutstandingAmount = max(0, round($totalInvoicedAmount - $totalPaidAmount, 2));

        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.reports.fulfillment', compact(
            'orders',
            'customers',
            'totalOrdersCount',
            'totalOrdersAmount',
            'totalInvoicedAmount',
            'totalPaidAmount',
            'totalOutstandingAmount'
        ));
    }

    /**
     * Rekap Penjualan per Barang (dengan Analisis HPP & Margin Kotor)
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
            ->withSum(['salesInvoiceItems as total_qty' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '>=', $dateFrom));
                if ($dateTo)   $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '<=', $dateTo));
            }], 'qty_invoiced')
            ->withSum(['salesInvoiceItems as total_amount' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '>=', $dateFrom));
                if ($dateTo)   $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '<=', $dateTo));
            }], 'subtotal')
            ->withSum(['salesInvoiceItems as total_cogs' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '>=', $dateFrom));
                if ($dateTo)   $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '<=', $dateTo));
            }], 'cogs_amount')
            ->withCount(['salesInvoiceItems as transaction_count' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '>=', $dateFrom));
                if ($dateTo)   $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '<=', $dateTo));
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
            $totalSales  = (float) $p->total_amount;
            $totalCogs   = (float) ($p->total_cogs ?: ($p->total_qty * $p->purchase_price));
            $grossMargin = $totalSales - $totalCogs;
            $marginPct   = $totalSales > 0 ? round(($grossMargin / $totalSales) * 100, 1) : 0;

            $p->calculated_cogs   = $totalCogs;
            $p->gross_margin      = $grossMargin;
            $p->margin_percentage = $marginPct;
            $p->avg_selling_price = $p->total_qty > 0 ? round($totalSales / $p->total_qty, 2) : $p->selling_price;
            return $p;
        });

        // Summary KPI cards
        $summaryQuery = Product::query();
        if ($categoryId) $summaryQuery->where('category_id', $categoryId);
        if ($search) {
            $summaryQuery->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        $totalItemsSold = (float) $summaryQuery->withSum(['salesInvoiceItems as total_qty' => function ($q) use ($dateFrom, $dateTo) {
            if ($dateFrom) $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '>=', $dateFrom));
            if ($dateTo)   $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '<=', $dateTo));
        }], 'qty_invoiced')->get()->sum('total_qty');

        $totalRevenue = (float) $summaryQuery->withSum(['salesInvoiceItems as total_amt' => function ($q) use ($dateFrom, $dateTo) {
            if ($dateFrom) $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '>=', $dateFrom));
            if ($dateTo)   $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '<=', $dateTo));
        }], 'subtotal')->get()->sum('total_amt');

        $totalCogsAll = (float) $summaryQuery->withSum(['salesInvoiceItems as total_cogs' => function ($q) use ($dateFrom, $dateTo) {
            if ($dateFrom) $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '>=', $dateFrom));
            if ($dateTo)   $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '<=', $dateTo));
        }], 'cogs_amount')->get()->sum('total_cogs');

        $totalGrossProfit = $totalRevenue - $totalCogsAll;
        $avgGrossMargin   = $totalRevenue > 0 ? round(($totalGrossProfit / $totalRevenue) * 100, 1) : 0;

        return view('sales.reports.recap-by-product', compact(
            'products', 'categories', 'totalItemsSold', 'totalRevenue', 'totalCogsAll',
            'totalGrossProfit', 'avgGrossMargin', 'dateFrom', 'dateTo', 'categoryId', 'search'
        ));
    }
}
