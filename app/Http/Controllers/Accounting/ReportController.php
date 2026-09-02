<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Models\SalesReturn;
use App\Models\StockDisposition;
use App\Models\Supplier;
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

    /**
     * 9. Kartu Hutang (Subsidiary Ledger Payable per Supplier)
     */
    public function ledgerPayable(Supplier $supplier, Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        // 1. Tagihan Invoices Pembelian
        $invoices = PurchaseInvoice::with('purchaseOrder')
            ->whereHas('purchaseOrder', fn($q) => $q->where('supplier_id', $supplier->id))
            ->get()
            ->map(function ($inv) {
                return (object) [
                    'date'             => $inv->invoice_date ? $inv->invoice_date->toDateString() : $inv->created_at->toDateString(),
                    'created_at'       => $inv->created_at,
                    'type'             => 'invoice',
                    'type_badge'       => 'primary',
                    'type_label'       => 'Invoice',
                    'document_number'  => $inv->invoice_number,
                    'reference_info'   => $inv->purchaseOrder ? $inv->purchaseOrder->po_number : '-',
                    'description'      => 'Tagihan Pembelian ' . ($inv->purchaseOrder ? '#' . $inv->purchaseOrder->po_number : ''),
                    'debit'            => 0, // Hutang berkurang
                    'credit'           => (float) $inv->total_amount, // Hutang bertambah
                    'link'             => route('purchase.invoices.show', $inv),
                ];
            });

        // 2. Pembayaran Hutang ke Supplier
        $payments = PurchasePayment::with(['purchaseInvoice.purchaseOrder'])
            ->whereHas('purchaseInvoice.purchaseOrder', fn($q) => $q->where('supplier_id', $supplier->id))
            ->get()
            ->map(function ($pay) {
                return (object) [
                    'date'             => $pay->payment_date ? $pay->payment_date->toDateString() : $pay->created_at->toDateString(),
                    'created_at'       => $pay->created_at,
                    'type'             => 'payment',
                    'type_badge'       => 'success',
                    'type_label'       => 'Pembayaran',
                    'document_number'  => $pay->reference_number ?: ('PAY-' . $pay->id),
                    'reference_info'   => $pay->purchaseInvoice ? $pay->purchaseInvoice->invoice_number : '-',
                    'description'      => 'Pembayaran Hutang Invoice ' . ($pay->purchaseInvoice ? '#' . $pay->purchaseInvoice->invoice_number : ''),
                    'debit'            => (float) $pay->amount, // Hutang berkurang
                    'credit'           => 0,
                    'link'             => $pay->purchaseInvoice ? route('purchase.invoices.show', $pay->purchaseInvoice) : null,
                ];
            });

        // 3. Retur Pembelian yang memotong hutang (completed)
        $returns = PurchaseReturn::with(['goodsReceipt.purchaseOrder', 'items'])
            ->where('supplier_id', $supplier->id)
            ->where('status', 'completed')
            ->get()
            ->map(function ($ret) {
                // Cek jurnal pembalikan hutang untuk mendapatkan nilai tepat yang memotong hutang
                $journal = JournalEntry::with('lines.account')
                    ->where('reference_type', PurchaseReturn::class)
                    ->where('reference_id', $ret->id)
                    ->where('status', 'posted')
                    ->first();

                $debtReversed = $journal
                    ? (float) $journal->lines->where('account.code', '2-1100')->sum('debit')
                    : (float) $ret->items->sum(fn($it) => $it->qty * $it->unit_cost);

                return (object) [
                    'date'             => $ret->return_date ? $ret->return_date->toDateString() : $ret->created_at->toDateString(),
                    'created_at'       => $ret->created_at,
                    'type'             => 'return',
                    'type_badge'       => 'danger',
                    'type_label'       => 'Retur Beli',
                    'document_number'  => $ret->return_number,
                    'reference_info'   => $ret->goodsReceipt ? $ret->goodsReceipt->receipt_number : '-',
                    'description'      => 'Retur Pembelian ' . ($ret->goodsReceipt ? '#' . $ret->goodsReceipt->receipt_number : ''),
                    'debit'            => $debtReversed, // Hutang berkurang
                    'credit'           => 0,
                    'link'             => route('purchase.returns.show', $ret),
                ];
            })
            ->filter(fn($r) => $r->debit > 0);

        // Gabungkan seluruh transaksi urut kronologis
        $allTransactions = $invoices->concat($payments)->concat($returns)
            ->sortBy(function ($t) {
                return $t->date . ' ' . $t->created_at;
            })
            ->values();

        // Hitung Saldo Awal (Beginning Balance) sebelum date_from
        $beginningBalance = 0;
        $filteredTransactions = collect();

        foreach ($allTransactions as $t) {
            $impact = $t->credit - $t->debit; // Pada Hutang: Kredit (+) menambah, Debit (-) mengurangi
            if ($dateFrom && $t->date < $dateFrom) {
                $beginningBalance += $impact;
            } else {
                if (!$dateTo || $t->date <= $dateTo) {
                    $filteredTransactions->push($t);
                }
            }
        }

        // Hitung Saldo Berjalan (Running Balance) untuk baris mutasi dalam periode
        $runningBalance = $beginningBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($filteredTransactions as $t) {
            $totalDebit += $t->debit;
            $totalCredit += $t->credit;
            $runningBalance += ($t->credit - $t->debit);
            $t->running_balance = $runningBalance;
        }

        $endingBalance = $runningBalance;

        return view('accounting.reports.ledger-payable', compact(
            'supplier', 'filteredTransactions', 'beginningBalance',
            'endingBalance', 'totalDebit', 'totalCredit', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * 10. Kartu Piutang (Subsidiary Ledger Receivable per Customer)
     */
    public function ledgerReceivable(Customer $customer, Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        // 1. Tagihan Invoices Penjualan
        $invoices = SalesInvoice::with('salesOrder')
            ->whereHas('salesOrder', fn($q) => $q->where('customer_id', $customer->id))
            ->get()
            ->map(function ($inv) {
                return (object) [
                    'date'             => $inv->invoice_date ? $inv->invoice_date->toDateString() : $inv->created_at->toDateString(),
                    'created_at'       => $inv->created_at,
                    'type'             => 'invoice',
                    'type_badge'       => 'primary',
                    'type_label'       => 'Invoice',
                    'document_number'  => $inv->invoice_number,
                    'reference_info'   => $inv->salesOrder ? $inv->salesOrder->so_number : '-',
                    'description'      => 'Tagihan Penjualan ' . ($inv->salesOrder ? '#' . $inv->salesOrder->so_number : ''),
                    'debit'            => (float) $inv->total_amount, // Piutang bertambah
                    'credit'           => 0, // Piutang berkurang
                    'link'             => route('sales.invoices.show', $inv),
                ];
            });

        // 2. Pembayaran Piutang dari Customer
        $payments = SalesPayment::with(['salesInvoice.salesOrder'])
            ->whereHas('salesInvoice.salesOrder', fn($q) => $q->where('customer_id', $customer->id))
            ->get()
            ->map(function ($pay) {
                return (object) [
                    'date'             => $pay->payment_date ? $pay->payment_date->toDateString() : $pay->created_at->toDateString(),
                    'created_at'       => $pay->created_at,
                    'type'             => 'payment',
                    'type_badge'       => 'success',
                    'type_label'       => 'Pembayaran',
                    'document_number'  => $pay->reference_number ?: ('PAY-' . $pay->id),
                    'reference_info'   => $pay->salesInvoice ? $pay->salesInvoice->invoice_number : '-',
                    'description'      => 'Penerimaan Pembayaran Piutang ' . ($pay->salesInvoice ? '#' . $pay->salesInvoice->invoice_number : ''),
                    'debit'            => 0,
                    'credit'           => (float) $pay->amount, // Piutang berkurang
                    'link'             => $pay->salesInvoice ? route('sales.invoices.show', $pay->salesInvoice) : null,
                ];
            });

        // 3. Retur Penjualan yang memotong piutang (received / completed)
        $returns = SalesReturn::with(['delivery.salesOrder', 'items'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['received', 'completed'])
            ->get()
            ->map(function ($ret) {
                $journal = JournalEntry::with('lines.account')
                    ->where('reference_type', SalesReturn::class)
                    ->where('reference_id', $ret->id)
                    ->where('status', 'posted')
                    ->first();

                $receivableReversed = $journal
                    ? (float) $journal->lines->where('account.code', '1-1200')->sum('credit')
                    : (float) $ret->items->sum(fn($it) => $it->qty * ($it->product->selling_price ?? 0));

                return (object) [
                    'date'             => $ret->return_date ? $ret->return_date->toDateString() : $ret->created_at->toDateString(),
                    'created_at'       => $ret->created_at,
                    'type'             => 'return',
                    'type_badge'       => 'danger',
                    'type_label'       => 'Retur Jual',
                    'document_number'  => $ret->return_number,
                    'reference_info'   => $ret->delivery ? $ret->delivery->delivery_number : '-',
                    'description'      => 'Retur Penjualan Customer ' . ($ret->delivery ? '#' . $ret->delivery->delivery_number : ''),
                    'debit'            => 0,
                    'credit'           => $receivableReversed, // Piutang berkurang
                    'link'             => route('sales.returns.show', $ret),
                ];
            })
            ->filter(fn($r) => $r->credit > 0);

        // Gabungkan seluruh transaksi urut kronologis
        $allTransactions = $invoices->concat($payments)->concat($returns)
            ->sortBy(function ($t) {
                return $t->date . ' ' . $t->created_at;
            })
            ->values();

        // Hitung Saldo Awal (Beginning Balance) sebelum date_from
        $beginningBalance = 0;
        $filteredTransactions = collect();

        foreach ($allTransactions as $t) {
            $impact = $t->debit - $t->credit; // Pada Piutang: Debit (+) menambah, Kredit (-) mengurangi
            if ($dateFrom && $t->date < $dateFrom) {
                $beginningBalance += $impact;
            } else {
                if (!$dateTo || $t->date <= $dateTo) {
                    $filteredTransactions->push($t);
                }
            }
        }

        // Hitung Saldo Berjalan (Running Balance) untuk baris mutasi dalam periode
        $runningBalance = $beginningBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($filteredTransactions as $t) {
            $totalDebit += $t->debit;
            $totalCredit += $t->credit;
            $runningBalance += ($t->debit - $t->credit);
            $t->running_balance = $runningBalance;
        }

        $endingBalance = $runningBalance;

        return view('accounting.reports.ledger-receivable', compact(
            'customer', 'filteredTransactions', 'beginningBalance',
            'endingBalance', 'totalDebit', 'totalCredit', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * 11. Rekap Hutang by Vendor
     */
    public function payablesByVendor(Request $request): View
    {
        $search = $request->input('q');
        $onlyOutstanding = $request->has('only_outstanding')
            ? $request->boolean('only_outstanding')
            : true;

        $query = Supplier::with(['purchaseInvoices' => function ($q) {
            $q->where('purchase_invoices.status', '!=', 'paid')->with('payments');
        }]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->get()->map(function ($supplier) {
            $openInvoices = $supplier->purchaseInvoices->filter(fn($inv) => $inv->outstanding_amount > 0.01);
            $supplier->open_invoices_count = $openInvoices->count();
            $supplier->total_payable = (float) $openInvoices->sum('outstanding_amount');

            $oldestInv = $openInvoices->sortBy('due_date')->first();
            if ($oldestInv) {
                $dueDate = Carbon::parse($oldestInv->due_date);
                $supplier->oldest_invoice_number = $oldestInv->invoice_number;
                $supplier->oldest_invoice_date = $oldestInv->invoice_date;
                $supplier->oldest_due_date = $dueDate;
                $diff = Carbon::today()->diffInDays($dueDate, false);
                $supplier->max_overdue_days = $diff < 0 ? abs($diff) : 0;
            } else {
                $supplier->oldest_invoice_number = null;
                $supplier->oldest_invoice_date = null;
                $supplier->oldest_due_date = null;
                $supplier->max_overdue_days = 0;
            }

            return $supplier;
        });

        if ($onlyOutstanding) {
            $suppliers = $suppliers->filter(fn($s) => $s->total_payable > 0)->values();
        }

        $totalVendors = $suppliers->count();
        $totalAllPayable = $suppliers->sum('total_payable');
        $totalOpenInvoices = $suppliers->sum('open_invoices_count');

        return view('accounting.reports.payables-by-vendor', compact(
            'suppliers', 'totalVendors', 'totalAllPayable', 'totalOpenInvoices', 'search', 'onlyOutstanding'
        ));
    }

    /**
     * 12. Rekap Piutang by Customer
     */
    public function receivablesByCustomer(Request $request): View
    {
        $search = $request->input('q');
        $onlyOutstanding = $request->has('only_outstanding')
            ? $request->boolean('only_outstanding')
            : true;

        $query = Customer::with(['salesInvoices' => function ($q) {
            $q->where('sales_invoices.status', '!=', 'paid')->with('payments');
        }]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->get()->map(function ($customer) {
            $openInvoices = $customer->salesInvoices->filter(fn($inv) => $inv->outstanding_amount > 0.01);
            $customer->open_invoices_count = $openInvoices->count();
            $customer->total_receivable = (float) $openInvoices->sum('outstanding_amount');

            $oldestInv = $openInvoices->sortBy('due_date')->first();
            if ($oldestInv) {
                $dueDate = Carbon::parse($oldestInv->due_date);
                $customer->oldest_invoice_number = $oldestInv->invoice_number;
                $customer->oldest_invoice_date = $oldestInv->invoice_date;
                $customer->oldest_due_date = $dueDate;
                $diff = Carbon::today()->diffInDays($dueDate, false);
                $customer->max_overdue_days = $diff < 0 ? abs($diff) : 0;
            } else {
                $customer->oldest_invoice_number = null;
                $customer->oldest_invoice_date = null;
                $customer->oldest_due_date = null;
                $customer->max_overdue_days = 0;
            }

            return $customer;
        });

        if ($onlyOutstanding) {
            $customers = $customers->filter(fn($c) => $c->total_receivable > 0)->values();
        }

        $totalCustomers = $customers->count();
        $totalAllReceivable = $customers->sum('total_receivable');
        $totalOpenInvoices = $customers->sum('open_invoices_count');

        return view('accounting.reports.receivables-by-customer', compact(
            'customers', 'totalCustomers', 'totalAllReceivable', 'totalOpenInvoices', 'search', 'onlyOutstanding'
        ));
    }
}
