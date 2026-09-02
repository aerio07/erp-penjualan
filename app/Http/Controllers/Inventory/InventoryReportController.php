<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryReportController extends Controller
{
    /**
     * Rekap Retur per Barang (Pembelian & Penjualan beserta Analisis Retur Rate %)
     */
    public function returnsByProduct(Request $request): View
    {
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');
        $categoryId = $request->input('category_id');
        $search     = $request->input('q');

        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        $query = Product::with('category')
            ->where('is_active', true)
            // 1. Qty Retur Pembelian (ke supplier)
            ->withSum(['purchaseReturnItems as purchase_return_qty' => function ($q) use ($dateFrom, $dateTo) {
                $q->whereHas('purchaseReturn', function ($pr) use ($dateFrom, $dateTo) {
                    $pr->where('status', 'completed');
                    if ($dateFrom) $pr->where('return_date', '>=', $dateFrom);
                    if ($dateTo)   $pr->where('return_date', '<=', $dateTo);
                });
            }], 'qty')
            // 2. Qty Retur Penjualan Kondisi Baik (masuk stok siap jual)
            ->withSum(['salesReturnItems as sales_return_good_qty' => function ($q) use ($dateFrom, $dateTo) {
                $q->where('condition', 'baik')
                  ->whereHas('salesReturn', function ($sr) use ($dateFrom, $dateTo) {
                      $sr->whereIn('status', ['received', 'completed']);
                      if ($dateFrom) $sr->where('return_date', '>=', $dateFrom);
                      if ($dateTo)   $sr->where('return_date', '<=', $dateTo);
                  });
            }], 'qty')
            // 3. Qty Retur Penjualan Kondisi Rusak (masuk karantina)
            ->withSum(['salesReturnItems as sales_return_damaged_qty' => function ($q) use ($dateFrom, $dateTo) {
                $q->where('condition', 'rusak')
                  ->whereHas('salesReturn', function ($sr) use ($dateFrom, $dateTo) {
                      $sr->whereIn('status', ['received', 'completed']);
                      if ($dateFrom) $sr->where('return_date', '>=', $dateFrom);
                      if ($dateTo)   $sr->where('return_date', '<=', $dateTo);
                  });
            }], 'qty')
            // 4. Total Qty Terjual (sebagai basis hitung % retur rate)
            ->withSum(['salesInvoiceItems as total_sold_qty' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '>=', $dateFrom));
                if ($dateTo)   $q->whereHas('salesInvoice', fn($si) => $si->where('invoice_date', '<=', $dateTo));
            }], 'qty_invoiced');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->get()->map(function ($p) {
            $pRetQty  = (int) $p->purchase_return_qty;
            $sRetGood = (int) $p->sales_return_good_qty;
            $sRetBad  = (int) $p->sales_return_damaged_qty;
            $totalSalesReturn = $sRetGood + $sRetBad;
            $totalSold = (int) $p->total_sold_qty;

            $returnRate = $totalSold > 0 ? round(($totalSalesReturn / $totalSold) * 100, 1) : 0;
            $pReturnValue = $pRetQty * $p->purchase_price;
            $sReturnValue = $totalSalesReturn * $p->selling_price;
            $totalReturnValue = $pReturnValue + $sReturnValue;

            $p->total_sales_return_qty = $totalSalesReturn;
            $p->return_rate = $returnRate;
            $p->purchase_return_value = $pReturnValue;
            $p->sales_return_value = $sReturnValue;
            $p->total_return_value = $totalReturnValue;
            $p->has_return = ($pRetQty > 0 || $totalSalesReturn > 0);

            return $p;
        });

        // Urutkan berdasarkan total retur terbanyak
        $sortedProducts = $products->sortByDesc(fn($p) => $p->total_sales_return_qty + $p->purchase_return_qty)->values();

        // Pagination manual untuk collection yang sudah ditransform
        $perPage = 20;
        $page = (int) $request->input('page', 1);
        $paginatedProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $sortedProducts->forPage($page, $perPage),
            $sortedProducts->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Metrik Ringkasan KPI
        $kpiPurchaseReturnQty = $products->sum('purchase_return_qty');
        $kpiSalesReturnQty    = $products->sum('total_sales_return_qty');
        $kpiReturnValue       = $products->sum('total_return_value');
        $kpiTotalSold         = $products->sum('total_sold_qty');
        $kpiAvgReturnRate     = $kpiTotalSold > 0 ? round(($kpiSalesReturnQty / $kpiTotalSold) * 100, 1) : 0;

        return view('inventory.reports.returns-by-product', compact(
            'paginatedProducts', 'categories', 'kpiPurchaseReturnQty', 'kpiSalesReturnQty',
            'kpiReturnValue', 'kpiAvgReturnRate', 'dateFrom', 'dateTo', 'categoryId', 'search'
        ));
    }
}
