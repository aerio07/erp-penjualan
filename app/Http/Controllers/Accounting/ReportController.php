<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalLine;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function profitLoss(Request $request): View
    {
        $year  = $request->input('year', date('Y'));
        $month = $request->input('month');

        $query = JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('chart_of_accounts', 'journal_lines.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->where('journal_entries.status', 'posted')
            ->whereYear('journal_entries.entry_date', $year);

        if ($month) {
            $query->whereMonth('journal_entries.entry_date', $month);
        }

        $byType = $query->selectRaw(
            'chart_of_accounts.type, chart_of_accounts.code, chart_of_accounts.name,
             SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit'
        )->groupBy('chart_of_accounts.type', 'chart_of_accounts.code', 'chart_of_accounts.name')
        ->orderBy('chart_of_accounts.code')
        ->get()
        ->groupBy('type');

        $revenue  = collect($byType->get('revenue', []))->sum(fn($r) => $r->total_credit - $r->total_debit);
        $cogs     = collect($byType->get('expense', []))->where('code', 'like', '5-1%')->sum(fn($r) => $r->total_debit - $r->total_credit);
        $expenses = collect($byType->get('expense', []))->where('code', 'not like', '5-1%')->sum(fn($r) => $r->total_debit - $r->total_credit);
        $grossProfit = $revenue - $cogs;
        $netProfit   = $grossProfit - $expenses;

        return view('accounting.reports.profit-loss', compact(
            'byType', 'revenue', 'cogs', 'expenses', 'grossProfit', 'netProfit', 'year', 'month'
        ));
    }

    public function receivables(Request $request): View
    {
        $invoices = SalesInvoice::with(['salesOrder.customer', 'payments'])
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->paginate(30);

        $openInvoices = SalesInvoice::with('payments')->where('status', '!=', 'paid')->get();
        $totalOutstanding = $openInvoices->sum->outstanding_amount;
        $overdue = $openInvoices
            ->where('due_date', '<', today())
            ->sum->outstanding_amount;

        return view('accounting.reports.receivables', compact('invoices', 'totalOutstanding', 'overdue'));
    }

    public function payables(Request $request): View
    {
        $invoices = PurchaseInvoice::with(['purchaseOrder.supplier', 'payments'])
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->paginate(30);

        $openInvoices = PurchaseInvoice::with('payments')->where('status', '!=', 'paid')->get();
        $totalOutstanding = $openInvoices->sum->outstanding_amount;
        $overdue = $openInvoices
            ->where('due_date', '<', today())
            ->sum->outstanding_amount;

        return view('accounting.reports.payables', compact('invoices', 'totalOutstanding', 'overdue'));
    }

    public function stockValuation(Request $request): View
    {
        // Hitung nilai persediaan per produk menggunakan average cost
        $products = \App\Models\Product::with(['stockMovements'])->where('is_active', true)->get()->map(function ($product) {
            $inMovements = $product->stockMovements()->whereIn('type', ['in', 'return_in', 'transfer_in'])->get();
            $totalQty    = $product->currentStock();
            $avgCost     = $inMovements->count() > 0
                ? $inMovements->sum(fn($m) => $m->quantity * $m->unit_cost) / max(1, $inMovements->sum('quantity'))
                : $product->purchase_price;

            $product->current_stock  = $totalQty;
            $product->avg_cost       = $avgCost;
            $product->stock_value    = $totalQty * $avgCost;

            return $product;
        });

        $totalValue = $products->sum('stock_value');

        return view('accounting.reports.stock-valuation', compact('products', 'totalValue'));
    }
}
