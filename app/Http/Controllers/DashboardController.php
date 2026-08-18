<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Services\StockService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(): View
    {
        $stats = [
            // Purchase
            'po_draft'            => PurchaseOrder::where('status', 'draft')->count(),
            'po_waiting_approval' => PurchaseOrder::where('status', 'waiting_approval')->count(),
            'po_confirmed'        => PurchaseOrder::where('status', 'confirmed')->count(),
            'purchase_payable'    => $this->sumOutstanding(PurchaseInvoice::class),

            // Sales
            'so_draft'            => SalesOrder::where('status', 'draft')->count(),
            'so_confirmed'        => SalesOrder::where('status', 'confirmed')->count(),
            'sales_receivable'    => $this->sumOutstanding(SalesInvoice::class),

            // Finance
            'overdue_payables'    => $this->sumOutstanding(PurchaseInvoice::class, true),
            'overdue_receivables' => $this->sumOutstanding(SalesInvoice::class, true),

            // Inventory
            'low_stock_count'    => $this->stockService->getLowStockProducts()->count(),
        ];

        // Grafik penjualan 6 bulan terakhir
        $salesChart = SalesInvoice::selectRaw("DATE_FORMAT(invoice_date,'%Y-%m') as month, SUM(total_amount) as total")
            ->where('invoice_date', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // Piutang jatuh tempo
        $upcomingReceivables = SalesInvoice::with('salesOrder.customer')
            ->where('status', '!=', 'paid')
            ->where('due_date', '<=', now()->addDays(7))
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Hutang jatuh tempo
        $upcomingPayables = PurchaseInvoice::with('purchaseOrder.supplier')
            ->where('status', '!=', 'paid')
            ->where('due_date', '<=', now()->addDays(7))
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'salesChart', 'upcomingReceivables', 'upcomingPayables'));
    }

    private function sumOutstanding(string $invoiceClass, bool $overdueOnly = false): float
    {
        $query = $invoiceClass::with('payments')->where('status', '!=', 'paid');

        if ($overdueOnly) {
            $query->where('due_date', '<', today());
        }

        return (float) $query->get()->sum->outstanding_amount;
    }
}
