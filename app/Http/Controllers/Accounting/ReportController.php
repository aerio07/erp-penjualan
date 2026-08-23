<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\StockDisposition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * 1. Buku Besar / General Ledger
     */
    public function ledger(Request $request): View
    {
        $accounts  = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        $accountId = $request->input('chart_of_account_id', $accounts->first()?->id);
        $dateFrom  = $request->input('date_from', date('Y-01-01'));
        $dateTo    = $request->input('date_to', date('Y-m-d'));

        $selectedAccount = $accountId ? ChartOfAccount::find($accountId) : null;
        $lines           = collect();
        $openingBalance  = 0;

        if ($selectedAccount) {
            $isAssetOrExpense = in_array($selectedAccount->type, ['asset', 'expense']);

            // Hitung opening balance sebelum date_from
            $openingQuery = JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_lines.chart_of_account_id', $selectedAccount->id)
                ->where('journal_entries.status', 'posted')
                ->whereDate('journal_entries.entry_date', '<', $dateFrom);

            if ($isAssetOrExpense) {
                $openingBalance = (float) $openingQuery->sum(DB::raw('debit - credit'));
            } else {
                $openingBalance = (float) $openingQuery->sum(DB::raw('credit - debit'));
            }

            // Ambil mutasi dalam periode date_from s/d date_to
            $rawLines = JournalLine::with(['journalEntry.reference', 'chartOfAccount'])
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_lines.chart_of_account_id', $selectedAccount->id)
                ->where('journal_entries.status', 'posted')
                ->whereDate('journal_entries.entry_date', '>=', $dateFrom)
                ->whereDate('journal_entries.entry_date', '<=', $dateTo)
                ->orderBy('journal_entries.entry_date', 'asc')
                ->orderBy('journal_entries.id', 'asc')
                ->select('journal_lines.*')
                ->get();

            // Hitung running balance per baris
            $running = $openingBalance;
            $lines   = $rawLines->map(function ($line) use (&$running, $isAssetOrExpense) {
                $change  = $isAssetOrExpense ? ($line->debit - $line->credit) : ($line->credit - $line->debit);
                $running += $change;
                $line->running_balance = $running;
                return $line;
            });
        }

        return view('accounting.reports.ledger', compact(
            'accounts', 'selectedAccount', 'accountId', 'dateFrom', 'dateTo', 'openingBalance', 'lines'
        ));
    }

    /**
     * 2. Neraca Saldo / Trial Balance
     */
    public function trialBalance(Request $request): View
    {
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));

        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();

        $totals = JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '<=', $asOfDate)
            ->selectRaw('chart_of_account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        $accounts->transform(function ($acc) use ($totals) {
            $t                  = $totals->get($acc->id);
            $acc->total_debit   = $t ? (float) $t->total_debit : 0;
            $acc->total_credit  = $t ? (float) $t->total_credit : 0;
            return $acc;
        });

        $grandTotalDebit  = $accounts->sum('total_debit');
        $grandTotalCredit = $accounts->sum('total_credit');
        $isBalanced       = abs($grandTotalDebit - $grandTotalCredit) < 0.01;

        return view('accounting.reports.trial-balance', compact(
            'accounts', 'asOfDate', 'grandTotalDebit', 'grandTotalCredit', 'isBalanced'
        ));
    }

    /**
     * 3. Laporan Arus Kas / Cash Flow
     */
    public function cashFlow(Request $request): View
    {
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));

        // Akun Kas & Bank (1-1100, 1-1110, 1-1120)
        $cashAccounts = ChartOfAccount::whereIn('code', ['1-1100', '1-1110', '1-1120'])->pluck('id');

        // Saldo Kas Awal Periode (sebelum date_from)
        $openingBalance = (float) JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.chart_of_account_id', $cashAccounts)
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '<', $dateFrom)
            ->sum(DB::raw('debit - credit'));

        // Transaksi Kas Periode date_from s/d date_to
        $cashLines = JournalLine::with(['journalEntry.reference', 'chartOfAccount'])
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.chart_of_account_id', $cashAccounts)
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '>=', $dateFrom)
            ->whereDate('journal_entries.entry_date', '<=', $dateTo)
            ->select('journal_lines.*')
            ->get();

        // Kas Masuk (Debit > 0)
        $inflows = $cashLines->where('debit', '>', 0)->groupBy(function ($line) {
            $ref = $line->journalEntry->reference_type;
            if ($ref === \App\Models\SalesPayment::class) {
                return 'Penerimaan Pembayaran Piutang Customer';
            } elseif ($ref === \App\Models\StockDisposition::class) {
                return 'Hasil Penjualan Barang Reject';
            }
            return 'Penerimaan Kas Lain-lain / Jurnal Manual';
        })->map(fn($group) => $group->sum('debit'));

        // Kas Keluar (Credit > 0)
        $outflows = $cashLines->where('credit', '>', 0)->groupBy(function ($line) {
            $ref = $line->journalEntry->reference_type;
            if ($ref === \App\Models\PurchasePayment::class) {
                return 'Pembayaran Hutang Supplier';
            }
            return 'Pengeluaran Kas Lain-lain / Jurnal Manual';
        })->map(fn($group) => $group->sum('credit'));

        $totalInflow   = $cashLines->sum('debit');
        $totalOutflow  = $cashLines->sum('credit');
        $closingBalance = $openingBalance + $totalInflow - $totalOutflow;

        return view('accounting.reports.cash-flow', compact(
            'dateFrom', 'dateTo', 'openingBalance', 'inflows', 'outflows',
            'totalInflow', 'totalOutflow', 'closingBalance'
        ));
    }

    /**
     * 4. Laporan Piutang & AR Aging
     */
    public function receivables(Request $request): View
    {
        $openInvoices = SalesInvoice::with(['salesOrder.customer', 'payments', 'items'])
            ->where('status', '!=', 'paid')
            ->get()
            ->map(function ($inv) {
                // DATEDIFF(today, due_date)
                $dueDate = Carbon::parse($inv->due_date);
                $today   = Carbon::today();
                $inv->days_overdue = $dueDate->diffInDays($today, false);

                if ($inv->days_overdue <= 0) {
                    $inv->aging_bucket = 'current'; // Belum jatuh tempo
                } elseif ($inv->days_overdue <= 30) {
                    $inv->aging_bucket = '1_30';
                } elseif ($inv->days_overdue <= 60) {
                    $inv->aging_bucket = '31_60';
                } elseif ($inv->days_overdue <= 90) {
                    $inv->aging_bucket = '61_90';
                } else {
                    $inv->aging_bucket = 'over_90';
                }

                return $inv;
            })
            ->filter(fn($inv) => $inv->outstanding_amount > 0)
            ->sortByDesc('days_overdue')
            ->values();

        $totalOutstanding = $openInvoices->sum('outstanding_amount');
        $bucketCurrent    = $openInvoices->where('aging_bucket', 'current')->sum('outstanding_amount');
        $bucket1to30      = $openInvoices->where('aging_bucket', '1_30')->sum('outstanding_amount');
        $bucket31to60     = $openInvoices->where('aging_bucket', '31_60')->sum('outstanding_amount');
        $bucket61to90     = $openInvoices->where('aging_bucket', '61_90')->sum('outstanding_amount');
        $bucketOver90     = $openInvoices->where('aging_bucket', 'over_90')->sum('outstanding_amount');

        return view('accounting.reports.receivables', compact(
            'openInvoices', 'totalOutstanding', 'bucketCurrent',
            'bucket1to30', 'bucket31to60', 'bucket61to90', 'bucketOver90'
        ));
    }

    /**
     * 5. Laporan Hutang & AP Aging
     */
    public function payables(Request $request): View
    {
        $openInvoices = PurchaseInvoice::with(['purchaseOrder.supplier', 'payments', 'items'])
            ->where('status', '!=', 'paid')
            ->get()
            ->map(function ($inv) {
                $dueDate = Carbon::parse($inv->due_date);
                $today   = Carbon::today();
                $inv->days_overdue = $dueDate->diffInDays($today, false);

                if ($inv->days_overdue <= 0) {
                    $inv->aging_bucket = 'current';
                } elseif ($inv->days_overdue <= 30) {
                    $inv->aging_bucket = '1_30';
                } elseif ($inv->days_overdue <= 60) {
                    $inv->aging_bucket = '31_60';
                } elseif ($inv->days_overdue <= 90) {
                    $inv->aging_bucket = '61_90';
                } else {
                    $inv->aging_bucket = 'over_90';
                }

                return $inv;
            })
            ->filter(fn($inv) => $inv->outstanding_amount > 0)
            ->sortByDesc('days_overdue')
            ->values();

        $totalOutstanding = $openInvoices->sum('outstanding_amount');
        $bucketCurrent    = $openInvoices->where('aging_bucket', 'current')->sum('outstanding_amount');
        $bucket1to30      = $openInvoices->where('aging_bucket', '1_30')->sum('outstanding_amount');
        $bucket31to60     = $openInvoices->where('aging_bucket', '31_60')->sum('outstanding_amount');
        $bucket61to90     = $openInvoices->where('aging_bucket', '61_90')->sum('outstanding_amount');
        $bucketOver90     = $openInvoices->where('aging_bucket', 'over_90')->sum('outstanding_amount');

        return view('accounting.reports.payables', compact(
            'openInvoices', 'totalOutstanding', 'bucketCurrent',
            'bucket1to30', 'bucket31to60', 'bucket61to90', 'bucketOver90'
        ));
    }

    /**
     * 6. Laporan Laba / Rugi (Profit & Loss)
     */
    public function profitLoss(Request $request): View
    {
        $dateFrom = $request->input('date_from', date('Y-01-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));

        $lines = JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('chart_of_accounts', 'journal_lines.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '>=', $dateFrom)
            ->whereDate('journal_entries.entry_date', '<=', $dateTo)
            ->whereIn('chart_of_accounts.type', ['revenue', 'expense'])
            ->selectRaw('chart_of_accounts.code, chart_of_accounts.name, chart_of_accounts.type, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->get()
            ->keyBy('code');

        // Revenue Breakdown
        $salesRevenue   = $lines->has('4-1100') ? ($lines->get('4-1100')->total_credit - $lines->get('4-1100')->total_debit) : 0;
        $salesReturn    = $lines->has('4-1200') ? ($lines->get('4-1200')->total_debit - $lines->get('4-1200')->total_credit) : 0;
        $salesDiscount  = $lines->has('4-1300') ? ($lines->get('4-1300')->total_debit - $lines->get('4-1300')->total_credit) : 0;
        $rejectRevenue  = $lines->has('4-1400') ? ($lines->get('4-1400')->total_credit - $lines->get('4-1400')->total_debit) : 0;
        $otherRevenue   = $lines->has('4-9100') ? ($lines->get('4-9100')->total_credit - $lines->get('4-9100')->total_debit) : 0;

        $netRevenue     = ($salesRevenue + $rejectRevenue + $otherRevenue) - ($salesReturn + $salesDiscount);

        // COGS Breakdown
        $cogsNormal     = $lines->has('5-1100') ? ($lines->get('5-1100')->total_debit - $lines->get('5-1100')->total_credit) : 0;
        $cogsReject     = $lines->has('5-1400') ? ($lines->get('5-1400')->total_debit - $lines->get('5-1400')->total_credit) : 0;
        $totalCogs      = $cogsNormal + $cogsReject;

        $grossProfit    = $netRevenue - $totalCogs;

        // Operating Expenses Breakdown
        $damagedExpense = $lines->has('5-1300') ? ($lines->get('5-1300')->total_debit - $lines->get('5-1300')->total_credit) : 0;
        $purchaseReturn = $lines->has('5-1200') ? ($lines->get('5-1200')->total_credit - $lines->get('5-1200')->total_debit) : 0; // Pembalikan beban

        $otherExpenses  = $lines->filter(fn($l) => str_starts_with($l->code, '5-2') || str_starts_with($l->code, '5-9'))
            ->sum(fn($l) => $l->total_debit - $l->total_credit);

        $totalOperatingExpense = $damagedExpense + $otherExpenses - $purchaseReturn;
        $netProfit              = $grossProfit - $totalOperatingExpense;

        return view('accounting.reports.profit-loss', compact(
            'dateFrom', 'dateTo', 'salesRevenue', 'salesReturn', 'salesDiscount',
            'rejectRevenue', 'otherRevenue', 'netRevenue', 'cogsNormal', 'cogsReject',
            'totalCogs', 'grossProfit', 'damagedExpense', 'purchaseReturn', 'otherExpenses',
            'totalOperatingExpense', 'netProfit'
        ));
    }

    /**
     * 7. Laporan Neraca / Balance Sheet
     */
    public function balanceSheet(Request $request): View
    {
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));

        // Posted Lines up to as_of_date
        $lines = JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('chart_of_accounts', 'journal_lines.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '<=', $asOfDate)
            ->selectRaw('chart_of_accounts.code, chart_of_accounts.name, chart_of_accounts.type, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->get()
            ->keyBy('code');

        // --- ASET ---
        $assetCodes = ['1-1100', '1-1110', '1-1120', '1-1200', '1-1210', '1-1300', '1-1400', '1-1500', '1-1900', '1-2100', '1-2900'];
        $assets = collect($assetCodes)->map(function ($code) use ($lines) {
            $l     = $lines->get($code);
            $debit  = $l ? (float) $l->total_debit : 0;
            $credit = $l ? (float) $l->total_credit : 0;
            $name   = $l ? $l->name : ChartOfAccount::where('code', $code)->value('name');

            // Untuk Akumulasi Penyusutan (1-2900), saldo normalnya Kredit (mengurangi Aset)
            $balance = ($code === '1-2900') ? ($credit - $debit) : ($debit - $credit);

            return [
                'code'    => $code,
                'name'    => $name ?? $code,
                'balance' => $balance,
            ];
        });
        $totalAssets = $assets->sum('balance');

        // --- KEWAJIBAN ---
        $liabilityCodes = ['2-1100', '2-1200', '2-1300', '2-1400', '2-1500', '2-2100'];
        $liabilities = collect($liabilityCodes)->map(function ($code) use ($lines) {
            $l     = $lines->get($code);
            $debit  = $l ? (float) $l->total_debit : 0;
            $credit = $l ? (float) $l->total_credit : 0;
            $name   = $l ? $l->name : ChartOfAccount::where('code', $code)->value('name');

            return [
                'code'    => $code,
                'name'    => $name ?? $code,
                'balance' => $credit - $debit,
            ];
        });
        $totalLiabilities = $liabilities->sum('balance');

        // --- EKUITAS ---
        $ownerCapital = $lines->has('3-1100') ? ($lines->get('3-1100')->total_credit - $lines->get('3-1100')->total_debit) : 0;
        $retainedEarnings = $lines->has('3-1200') ? ($lines->get('3-1200')->total_credit - $lines->get('3-1200')->total_debit) : 0;

        // Laba / Rugi Berjalan Kumulatif (sampai as_of_date)
        $totalRevenue = $lines->filter(fn($l) => $l->type === 'revenue')
            ->sum(fn($l) => $l->total_credit - $l->total_debit);
        $totalExpense = $lines->filter(fn($l) => $l->type === 'expense')
            ->sum(fn($l) => $l->total_debit - $l->total_credit);

        $currentProfit = $totalRevenue - $totalExpense;

        $totalEquity = $ownerCapital + $retainedEarnings + $currentProfit;
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

        return view('accounting.reports.balance-sheet', compact(
            'asOfDate', 'assets', 'totalAssets', 'liabilities', 'totalLiabilities',
            'ownerCapital', 'retainedEarnings', 'currentProfit', 'totalEquity',
            'totalLiabilitiesAndEquity', 'isBalanced'
        ));
    }

    /**
     * 8. Valuation Stok
     */
    public function stockValuation(Request $request): View
    {
        $products = \App\Models\Product::with(['stockMovements'])->where('is_active', true)->get()->map(function ($product) {
            $inMovements = $product->stockMovements()->whereIn('type', ['in', 'return_in', 'transfer_in'])->get();
            $totalQty    = $product->currentStock();
            $avgCost     = $inMovements->count() > 0
                ? $inMovements->sum(fn($m) => $m->quantity * $m->unit_cost) / max(1, $inMovements->sum('quantity'))
                : $product->purchase_price;

            $product->current_stock = $totalQty;
            $product->avg_cost      = $avgCost;
            $product->stock_value   = $totalQty * $avgCost;

            return $product;
        });

        $totalValue = $products->sum('stock_value');

        return view('accounting.reports.stock-valuation', compact('products', 'totalValue'));
    }
}
