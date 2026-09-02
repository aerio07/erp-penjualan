<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\JournalLine;
use App\Models\ProcurementDemand;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $role = strtolower($user->role);

        // --- BARIS 1: Stat Cards ---
        $totalPiutang = $this->getTotalPiutang();
        $totalHutang  = $this->getTotalHutang();
        $labaBulanIni = $this->getLabaBulanIni();
        $saldoKas     = $this->getSaldoKas();

        // --- BARIS 2: Grafik ---
        $trenPenjualan = $this->getTrenPenjualan30Hari();
        $topProduk     = $this->getTopProdukTerlaris();

        // --- BARIS 3: Notifikasi / Alert Aksi ---
        $alerts = $this->getAlerts($role);

        // --- BARIS 4: Aktivitas Terbaru ---
        $aktivitas = $this->getAktivitasTerbaru();

        // Extra info untuk list preview jatuh tempo (≤ 7 hari dari hari ini) & low stock
        $today = Carbon::today();
        $dueThreshold = $today->copy()->addDays(7);

        $allUpcomingReceivables = SalesInvoice::with(['salesOrder.customer', 'payments', 'items'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $dueThreshold)
            ->orderBy('due_date')
            ->get()
            ->map(function ($inv) use ($today) {
                $dueDate = Carbon::parse($inv->due_date);
                $inv->days_remaining = max(0, $today->diffInDays($dueDate, false));
                return $inv;
            })
            ->filter(fn($invoice) => $invoice->outstanding_amount > 0.01)
            ->values();

        $upcomingReceivables = $allUpcomingReceivables->take(5);
        $totalUpcomingReceivables = (float) $allUpcomingReceivables->sum('outstanding_amount');

        $allUpcomingPayables = PurchaseInvoice::with(['purchaseOrder.supplier', 'payments', 'items'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $dueThreshold)
            ->orderBy('due_date')
            ->get()
            ->map(function ($inv) use ($today) {
                $dueDate = Carbon::parse($inv->due_date);
                $inv->days_remaining = max(0, $today->diffInDays($dueDate, false));
                return $inv;
            })
            ->filter(fn($invoice) => $invoice->outstanding_amount > 0.01)
            ->values();

        $upcomingPayables = $allUpcomingPayables->take(5);
        $totalUpcomingPayables = (float) $allUpcomingPayables->sum('outstanding_amount');

        $lowStockProducts = $this->stockService->getLowStockProducts()->take(5);

        // Status Antrian Operasional & Logistik
        $operationalQueues = [
            'so_ready_to_ship'   => SalesOrder::whereIn('status', ['confirmed', 'partially_delivered'])->count(),
            'po_waiting_receipt' => PurchaseOrder::whereIn('status', ['confirmed', 'partially_received'])->count(),
            'backorder_count'    => ProcurementDemand::whereIn('status', ['pending', 'ordered'])->count(),
        ];

        return view('dashboard', compact(
            'role',
            'totalPiutang',
            'totalHutang',
            'labaBulanIni',
            'saldoKas',
            'trenPenjualan',
            'topProduk',
            'alerts',
            'aktivitas',
            'upcomingReceivables',
            'upcomingPayables',
            'totalUpcomingReceivables',
            'totalUpcomingPayables',
            'lowStockProducts',
            'operationalQueues'
        ));
    }

    private function getTotalPiutang(): float
    {
        return (float) SalesInvoice::with(['payments', 'items'])
            ->where('status', '!=', 'paid')
            ->get()
            ->sum->outstanding_amount;
    }

    private function getTotalHutang(): float
    {
        return (float) PurchaseInvoice::with(['payments', 'items'])
            ->where('status', '!=', 'paid')
            ->get()
            ->sum->outstanding_amount;
    }

    private function getLabaBulanIni(): float
    {
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth   = now()->endOfMonth()->toDateString();

        $revenue = (float) JournalLine::whereHas('journalEntry', function ($q) use ($startOfMonth, $endOfMonth) {
            $q->where('status', 'posted')
              ->whereBetween('entry_date', [$startOfMonth, $endOfMonth]);
        })->whereHas('chartOfAccount', function ($q) {
            $q->where('type', 'revenue');
        })->selectRaw('SUM(credit - debit) as net')->value('net') ?? 0;

        $expense = (float) JournalLine::whereHas('journalEntry', function ($q) use ($startOfMonth, $endOfMonth) {
            $q->where('status', 'posted')
              ->whereBetween('entry_date', [$startOfMonth, $endOfMonth]);
        })->whereHas('chartOfAccount', function ($q) {
            $q->where('type', 'expense');
        })->selectRaw('SUM(debit - credit) as net')->value('net') ?? 0;

        return $revenue - $expense;
    }

    private function getSaldoKas(): float
    {
        return (float) JournalLine::whereHas('journalEntry', function ($q) {
            $q->where('status', 'posted');
        })->whereHas('chartOfAccount', function ($q) {
            $q->where('type', 'asset')
              ->where(function ($sq) {
                  $sq->where('code', 'like', '1-11%')
                     ->orWhere('name', 'like', '%Kas%')
                     ->orWhere('name', 'like', '%Bank%');
              });
        })->selectRaw('SUM(debit - credit) as balance')->value('balance') ?? 0;
    }

    private function getTrenPenjualan30Hari(): array
    {
        $startDate = now()->subDays(29)->startOfDay();

        $sales = SalesInvoice::selectRaw('DATE(invoice_date) as date, SUM(total_amount) as total')
            ->where('invoice_date', '>=', $startDate->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Fill all 30 days so chart timeline is continuous
        $dates = [];
        $totals = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $dates[]  = date('d M', strtotime($d));
            $totals[] = (float) ($sales[$d] ?? 0);
        }

        return [
            'categories' => $dates,
            'data'       => $totals,
        ];
    }

    private function getTopProdukTerlaris(): array
    {
        $startDate = now()->subDays(30)->startOfDay();

        $top = DB::table('sales_invoice_items')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
            ->where('sales_invoices.invoice_date', '>=', $startDate->toDateString())
            ->select('products.name', DB::raw('SUM(sales_invoice_items.qty_invoiced) as total_qty'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'labels' => $top->pluck('name')->toArray(),
            'series' => $top->pluck('total_qty')->map(fn($v) => (int) $v)->toArray(),
        ];
    }

    private function getAlerts(string $role): array
    {
        $lowStockCount = $this->stockService->getLowStockProducts()->count();

        // Hitung produk yang punya stok karantina tersedia
        $quarantineCount = Product::where('is_active', true)
            ->get()
            ->filter(fn($p) => $this->stockService->getQuarantineStockAvailable($p->id) > 0)
            ->count();

        $poApprovalCount = PurchaseOrder::where('status', 'waiting_approval')->count();

        $today = Carbon::today();
        $dueThreshold = $today->copy()->addDays(7);

        $dueReceivablesCount = SalesInvoice::with(['payments', 'items'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $dueThreshold)
            ->get()
            ->filter(fn($invoice) => $invoice->outstanding_amount > 0.01)
            ->count();

        $duePayablesCount = PurchaseInvoice::with(['payments', 'items'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $dueThreshold)
            ->get()
            ->filter(fn($invoice) => $invoice->outstanding_amount > 0.01)
            ->count();

        return [
            'po_waiting_approval' => $poApprovalCount,
            'due_receivables'     => $dueReceivablesCount,
            'due_payables'        => $duePayablesCount,
            'low_stock'           => $lowStockCount,
            'quarantine_pending'  => $quarantineCount,
        ];
    }

    private function getAktivitasTerbaru(): array
    {
        $activities = [];

        // 1. Sales Invoices
        foreach (SalesInvoice::with('salesOrder.customer')->latest('created_at')->limit(3)->get() as $inv) {
            $activities[] = [
                'type'       => 'Sales Invoice',
                'icon'       => 'fa-file-invoice-dollar',
                'color'      => '#10b981',
                'ref'        => $inv->invoice_number,
                'desc'       => 'Invoice ke ' . ($inv->salesOrder->customer->name ?? 'Customer'),
                'amount'     => $inv->effective_total_amount,
                'status'     => $inv->status,
                'created_at' => $inv->created_at,
                'url'        => route('sales.invoices.show', $inv->id),
            ];
        }

        // 2. Purchase Orders
        foreach (PurchaseOrder::with('supplier')->latest('created_at')->limit(3)->get() as $po) {
            $activities[] = [
                'type'       => 'Purchase Order',
                'icon'       => 'fa-cart-shopping',
                'color'      => '#6366f1',
                'ref'        => $po->po_number,
                'desc'       => 'PO ke ' . ($po->supplier->name ?? 'Supplier'),
                'amount'     => $po->total_amount,
                'status'     => $po->status,
                'created_at' => $po->created_at,
                'url'        => route('purchase.orders.show', $po->id),
            ];
        }

        // 3. Stock Movements
        foreach (StockMovement::with('product', 'warehouse')->latest('created_at')->limit(3)->get() as $sm) {
            $activities[] = [
                'type'       => 'Mutasi Stok',
                'icon'       => 'fa-boxes-stacked',
                'color'      => '#f59e0b',
                'ref'        => strtoupper($sm->type),
                'desc'       => ($sm->product->name ?? 'Produk') . ' (' . $sm->quantity . ' pcs) @ ' . ($sm->warehouse->name ?? 'Gudang'),
                'amount'     => null,
                'status'     => 'done',
                'created_at' => $sm->created_at,
                'url'        => route('inventory.movements.index'),
            ];
        }

        // Sort descending by created_at
        usort($activities, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        return array_slice($activities, 0, 7);
    }
}
