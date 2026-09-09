<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Customer;
use App\Models\ProductCategory;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
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

        // Akun Kas & Bank (dinamis via flag is_cash_bank)
        $cashAccountIds = ChartOfAccount::cashAndBank()->pluck('id');
        $cashAccountsList = ChartOfAccount::cashAndBank()->orderBy('code')->get();

        // Saldo Kas Awal Periode (sebelum date_from)
        $openingBalance = (float) JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.chart_of_account_id', $cashAccountIds)
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '<', $dateFrom)
            ->sum(DB::raw('debit - credit'));

        // Transaksi Kas Periode date_from s/d date_to
        $cashLines = JournalLine::with(['journalEntry.reference', 'chartOfAccount'])
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.chart_of_account_id', $cashAccountIds)
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

        // Data forecast tetap disertakan untuk kompatibilitas pengujian/backward compatibility
        $forecastPeriod = $request->input('forecast_period', 'current');
        $forecastData = $this->getForecastData($forecastPeriod);

        return view('accounting.reports.cash-flow', array_merge(compact(
            'dateFrom', 'dateTo', 'openingBalance', 'inflows', 'outflows',
            'totalInflow', 'totalOutflow', 'closingBalance'
        ), $forecastData));
    }

    /**
     * 3b. Proyeksi Kas / Cash Flow Forecast (Dedicated Menu & Halaman)
     */
    public function cashForecast(Request $request): View
    {
        $forecastPeriod = $request->input('forecast_period', 'current');
        $data = $this->getForecastData($forecastPeriod);

        return view('accounting.reports.cash-forecast', $data);
    }

    /**
     * Helper perhitungan data Proyeksi Kas (Forecast)
     */
    private function getForecastData(string $forecastPeriod): array
    {
        $today = Carbon::today();
        $cashAccountIds = ChartOfAccount::cashAndBank()->pluck('id');
        $cashAccountsList = ChartOfAccount::cashAndBank()->orderBy('code')->get();

        // Parse periode forecast
        [$fcStart, $fcEnd, $fcLabel, $fcIsCurrent] = $this->parseForecastPeriod($forecastPeriod, $today);

        // Saldo Awal Forecast (aktual atau estimasi berantai)
        $fcSaldoAwal = $this->getForecastSaldoAwal($fcStart, $cashAccountIds, $fcIsCurrent);
        $fcSaldoAwalLabel = $fcIsCurrent ? 'Posisi Kas Aktual' : 'Estimasi Berantai';

        // Breakdown saldo awal per akun
        $fcSaldoBreakdown = $cashAccountsList->map(function ($account) use ($fcStart, $fcIsCurrent, $today) {
            $query = JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_lines.chart_of_account_id', $account->id)
                ->where('journal_entries.status', 'posted');

            if ($fcIsCurrent) {
                $query->whereDate('journal_entries.entry_date', '<=', $today->toDateString());
            } else {
                $query->whereDate('journal_entries.entry_date', '<', $fcStart->toDateString());
            }

            $balance = (float) $query->sum(DB::raw('debit - credit'));
            return (object) ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
        });

        // Est. Penerimaan dari Piutang (jatuh tempo s/d akhir periode - visibilitas penuh s/d $fcEnd)
        $fcReceivables = SalesInvoice::with(['salesOrder.customer', 'payments', 'items'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<=', $fcEnd->toDateString())
            ->get()
            ->filter(fn($inv) => $inv->outstanding_amount > 0.01);

        // Metrik Kumulatif s/d Akhir Periode
        $fcTotalReceivableCumulative = (float) $fcReceivables->sum('outstanding_amount');

        // Klasifikasi & Turunan Matematis Tunggal (Cumulative - Prior)
        if ($fcIsCurrent) {
            $fcTotalReceivablePrior  = 0.0;
            $fcTotalReceivablePeriod = $fcTotalReceivableCumulative;
        } else {
            $fcTotalReceivablePrior  = (float) $fcReceivables->filter(fn($inv) => Carbon::parse($inv->due_date)->lt($fcStart))->sum('outstanding_amount');
            $fcTotalReceivablePeriod = $fcTotalReceivableCumulative - $fcTotalReceivablePrior;
        }

        $fcReceivableByCustomer = $fcReceivables
            ->groupBy(fn($inv) => $inv->salesOrder?->customer?->id ?? 0)
            ->map(function ($invoices) use ($today, $fcStart, $fcIsCurrent) {
                $customer = $invoices->first()->salesOrder?->customer;
                $notes = $invoices->sortBy('due_date')->map(function ($inv) use ($today, $fcStart, $fcIsCurrent) {
                    $dueDate = Carbon::parse($inv->due_date);
                    $isOverdue = $dueDate->lt($today);
                    $isPrior = !$fcIsCurrent && $dueDate->lt($fcStart);
                    return (object) [
                        'id'                 => $inv->id,
                        'invoice_number'     => $inv->invoice_number,
                        'invoice_date'       => $inv->invoice_date,
                        'due_date'           => $inv->due_date,
                        'due_date_formatted' => $dueDate->format('d/m/Y'),
                        'total_amount'       => (float) $inv->effective_total_amount,
                        'paid'               => (float) $inv->total_paid,
                        'outstanding'        => (float) $inv->outstanding_amount,
                        'is_overdue'         => $isOverdue,
                        'is_prior'           => $isPrior,
                        'classification'     => $isPrior ? 'prior' : 'current',
                        'days_overdue'       => $isOverdue ? $dueDate->diffInDays($today) : 0,
                    ];
                });
                return (object) [
                    'customer_id'   => $customer?->id ?? 0,
                    'customer_name' => $customer?->name ?? 'Tanpa Customer',
                    'customer_code' => $customer?->code ?? '-',
                    'total'         => (float) $invoices->sum('outstanding_amount'),
                    'total_current' => (float) $notes->where('is_prior', false)->sum('outstanding'),
                    'total_prior'   => (float) $notes->where('is_prior', true)->sum('outstanding'),
                    'count'         => $invoices->count(),
                    'count_current' => $notes->where('is_prior', false)->count(),
                    'count_prior'   => $notes->where('is_prior', true)->count(),
                    'invoices'      => $notes->values(),
                ];
            })->sortByDesc('total')->values();

        // Est. Pengeluaran dari Hutang (jatuh tempo s/d akhir periode - visibilitas penuh s/d $fcEnd)
        $fcPayables = PurchaseInvoice::with(['purchaseOrder.supplier', 'payments', 'items'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<=', $fcEnd->toDateString())
            ->get()
            ->filter(fn($inv) => $inv->outstanding_amount > 0.01);

        // Metrik Kumulatif s/d Akhir Periode
        $fcTotalPayableCumulative = (float) $fcPayables->sum('outstanding_amount');

        // Klasifikasi & Turunan Matematis Tunggal (Cumulative - Prior)
        if ($fcIsCurrent) {
            $fcTotalPayablePrior  = 0.0;
            $fcTotalPayablePeriod = $fcTotalPayableCumulative;
        } else {
            $fcTotalPayablePrior  = (float) $fcPayables->filter(fn($inv) => Carbon::parse($inv->due_date)->lt($fcStart))->sum('outstanding_amount');
            $fcTotalPayablePeriod = $fcTotalPayableCumulative - $fcTotalPayablePrior;
        }

        $fcPayableBySupplier = $fcPayables
            ->groupBy(fn($inv) => $inv->purchaseOrder?->supplier?->id ?? 0)
            ->map(function ($invoices) use ($today, $fcStart, $fcIsCurrent) {
                $supplier = $invoices->first()->purchaseOrder?->supplier;
                $notes = $invoices->sortBy('due_date')->map(function ($inv) use ($today, $fcStart, $fcIsCurrent) {
                    $dueDate = Carbon::parse($inv->due_date);
                    $isOverdue = $dueDate->lt($today);
                    $isPrior = !$fcIsCurrent && $dueDate->lt($fcStart);
                    return (object) [
                        'id'                 => $inv->id,
                        'invoice_number'     => $inv->invoice_number,
                        'invoice_date'       => $inv->invoice_date,
                        'due_date'           => $inv->due_date,
                        'due_date_formatted' => $dueDate->format('d/m/Y'),
                        'total_amount'       => (float) $inv->effective_total_amount,
                        'paid'               => (float) $inv->total_paid,
                        'outstanding'        => (float) $inv->outstanding_amount,
                        'is_overdue'         => $isOverdue,
                        'is_prior'           => $isPrior,
                        'classification'     => $isPrior ? 'prior' : 'current',
                        'days_overdue'       => $isOverdue ? $dueDate->diffInDays($today) : 0,
                    ];
                });
                return (object) [
                    'supplier_id'   => $supplier?->id ?? 0,
                    'supplier_name' => $supplier?->name ?? 'Tanpa Supplier',
                    'supplier_code' => $supplier?->code ?? '-',
                    'total'         => (float) $invoices->sum('outstanding_amount'),
                    'total_current' => (float) $notes->where('is_prior', false)->sum('outstanding'),
                    'total_prior'   => (float) $notes->where('is_prior', true)->sum('outstanding'),
                    'count'         => $invoices->count(),
                    'count_current' => $notes->where('is_prior', false)->count(),
                    'count_prior'   => $notes->where('is_prior', true)->count(),
                    'invoices'      => $notes->values(),
                ];
            })->sortByDesc('total')->values();

        // Saldo Akhir Proyeksi
        $fcSaldoAkhir = $fcSaldoAwal + $fcTotalReceivablePeriod - $fcTotalPayablePeriod;
        $fcHealthThreshold = ($fcIsCurrent ? $fcTotalPayableCumulative : $fcTotalPayablePeriod) * 0.20;
        if ($fcSaldoAkhir < 0) {
            $fcHealthBadge = 'danger';  $fcHealthLabel = 'Defisit Kas';
        } elseif ($fcSaldoAkhir < $fcHealthThreshold) {
            $fcHealthBadge = 'warning'; $fcHealthLabel = 'Kas Menipis';
        } else {
            $fcHealthBadge = 'success'; $fcHealthLabel = 'Kas Sehat';
        }

        $fcPeriodOptions = [
            'current' => 'Bulan Ini (' . $today->copy()->startOfMonth()->translatedFormat('F Y') . ')',
            'next'    => 'Bulan Depan (' . $today->copy()->addMonth()->startOfMonth()->translatedFormat('F Y') . ')',
            'next2'   => '2 Bulan Depan (' . $today->copy()->addMonths(2)->startOfMonth()->translatedFormat('F Y') . ')',
        ];

        // Alias untuk kompatibilitas view & test lama
        $fcTotalReceivable = $fcTotalReceivableCumulative;
        $fcTotalPayable    = $fcTotalPayableCumulative;

        return compact(
            'forecastPeriod', 'fcStart', 'fcEnd', 'fcLabel', 'fcIsCurrent',
            'fcPeriodOptions',
            'fcSaldoAwal', 'fcSaldoAwalLabel', 'fcSaldoBreakdown',
            'fcTotalReceivable', 'fcTotalReceivableCumulative', 'fcTotalReceivablePeriod', 'fcTotalReceivablePrior', 'fcReceivableByCustomer',
            'fcTotalPayable', 'fcTotalPayableCumulative', 'fcTotalPayablePeriod', 'fcTotalPayablePrior', 'fcPayableBySupplier',
            'fcSaldoAkhir', 'fcHealthBadge', 'fcHealthLabel',
            'cashAccountsList'
        );
    }

    /**
     * Parse pilihan periode forecast dropdown.
     */
    private function parseForecastPeriod(string $period, Carbon $today): array
    {
        switch ($period) {
            case 'next':
                $start = $today->copy()->addMonth()->startOfMonth();
                $end   = $start->copy()->endOfMonth();
                $label = $start->translatedFormat('F Y');
                return [$start, $end, $label, false];
            case 'next2':
                $start = $today->copy()->addMonths(2)->startOfMonth();
                $end   = $start->copy()->endOfMonth();
                $label = $start->translatedFormat('F Y');
                return [$start, $end, $label, false];
            case 'current':
            default:
                $start = $today->copy()->startOfMonth();
                $end   = $today->copy()->endOfMonth();
                $label = $start->translatedFormat('F Y');
                return [$start, $end, $label, true];
        }
    }

    /**
     * Hitung Saldo Awal Kas rekursif untuk forecast (bebas double-counting antar bulan).
     */
    private function getForecastSaldoAwal(Carbon $periodStart, $cashAccountIds, bool $isCurrent = false): float
    {
        $today = Carbon::today();
        $currentMonthStart = $today->copy()->startOfMonth();

        if ($periodStart->lte($currentMonthStart)) {
            $query = JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->whereIn('journal_lines.chart_of_account_id', $cashAccountIds)
                ->where('journal_entries.status', 'posted');

            if ($isCurrent) {
                $query->whereDate('journal_entries.entry_date', '<=', $today->toDateString());
            } else {
                $query->whereDate('journal_entries.entry_date', '<', $periodStart->toDateString());
            }

            return (float) $query->sum(DB::raw('debit - credit'));
        }

        $prevStart = $periodStart->copy()->subMonth()->startOfMonth();
        $prevEnd   = $prevStart->copy()->endOfMonth();
        $prevIsCurrent = $prevStart->isSameMonth($currentMonthStart);
        $prevSaldoAwal = $this->getForecastSaldoAwal($prevStart, $cashAccountIds, $prevIsCurrent);

        $prevReceivables = SalesInvoice::with(['payments', 'items'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<=', $prevEnd->toDateString())
            ->get()->filter(fn($inv) => $inv->outstanding_amount > 0.01);

        $prevPayables = PurchaseInvoice::with(['payments', 'items'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<=', $prevEnd->toDateString())
            ->get()->filter(fn($inv) => $inv->outstanding_amount > 0.01);

        $prevRecCumulative = (float) $prevReceivables->sum('outstanding_amount');
        $prevPayCumulative = (float) $prevPayables->sum('outstanding_amount');

        if ($prevIsCurrent) {
            $netChange = $prevRecCumulative - $prevPayCumulative;
        } else {
            // Menggunakan turunan selisih: Cumulative - Prior
            $prevRecPrior = (float) $prevReceivables->filter(fn($inv) => Carbon::parse($inv->due_date)->lt($prevStart))->sum('outstanding_amount');
            $prevPayPrior = (float) $prevPayables->filter(fn($inv) => Carbon::parse($inv->due_date)->lt($prevStart))->sum('outstanding_amount');
            $prevRecPeriod = $prevRecCumulative - $prevRecPrior;
            $prevPayPeriod = $prevPayCumulative - $prevPayPrior;
            $netChange = $prevRecPeriod - $prevPayPeriod;
        }

        return $prevSaldoAwal + $netChange;
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

        // --- ASET (Dinamis seluruh CoA tipe asset) ---
        $assetAccounts = ChartOfAccount::where('type', 'asset')->where('is_active', true)->orderBy('code')->get();
        $assets = $assetAccounts->map(function ($acc) use ($lines) {
            $l      = $lines->get($acc->code);
            $debit  = $l ? (float) $l->total_debit : 0;
            $credit = $l ? (float) $l->total_credit : 0;

            // Akun kontra aset (seperti Akumulasi Penyusutan 1-2900) bersaldo normal kredit (mengurangi total aset)
            $isContra = ($acc->code === '1-2900' || $acc->normal_balance === 'credit');
            $balance  = $isContra ? ($debit - $credit) : ($debit - $credit);

            return [
                'code'      => $acc->code,
                'name'      => $acc->name,
                'balance'   => $balance,
                'is_contra' => $isContra,
            ];
        });
        $totalAssets = $assets->sum('balance');

        // --- KEWAJIBAN (Dinamis seluruh CoA tipe liability) ---
        $liabilityAccounts = ChartOfAccount::where('type', 'liability')->where('is_active', true)->orderBy('code')->get();
        $liabilities = $liabilityAccounts->map(function ($acc) use ($lines) {
            $l      = $lines->get($acc->code);
            $debit  = $l ? (float) $l->total_debit : 0;
            $credit = $l ? (float) $l->total_credit : 0;

            return [
                'code'    => $acc->code,
                'name'    => $acc->name,
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
                $journal = JournalEntry::with('lines.chartOfAccount')
                    ->where('reference_type', PurchaseReturn::class)
                    ->where('reference_id', $ret->id)
                    ->where('status', 'posted')
                    ->first();

                $debtReversed = $journal
                    ? (float) $journal->lines->where('chartOfAccount.code', '2-1100')->sum('debit')
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
                $journal = JournalEntry::with('lines.chartOfAccount')
                    ->where('reference_type', SalesReturn::class)
                    ->where('reference_id', $ret->id)
                    ->where('status', 'posted')
                    ->first();

                $receivableReversed = $journal
                    ? (float) $journal->lines->where('chartOfAccount.code', '1-1200')->sum('credit')
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

    /**
     * 13. Laporan Tagihan Piutang Akan Jatuh Tempo
     */
    public function receivablesUpcoming(Request $request): View
    {
        $days = (int) $request->input('days', 7);
        $search = $request->input('q');
        $today = Carbon::today();
        $targetDate = $today->copy()->addDays($days);

        $query = SalesInvoice::with(['salesOrder.customer', 'payments', 'items'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $targetDate);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('salesOrder.customer', fn($c) => $c->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($inv) use ($today) {
                $dueDate = Carbon::parse($inv->due_date);
                $diff = $today->diffInDays($dueDate, false);
                $inv->days_remaining = max(0, $diff);
                return $inv;
            })
            // Filter hanya tagihan yang masih ada sisa outstanding setelah memperhitungkan retur & pembayaran
            ->filter(fn($inv) => $inv->outstanding_amount > 0.01)
            ->values();

        $totalUpcomingAmount = (float) $invoices->sum('outstanding_amount');
        $totalUpcomingCount = $invoices->count();

        return view('accounting.reports.receivables-upcoming', compact(
            'invoices', 'days', 'search', 'totalUpcomingAmount', 'totalUpcomingCount'
        ));
    }

    /**
     * 14. Laporan Tagihan Hutang Akan Jatuh Tempo
     */
    public function payablesUpcoming(Request $request): View
    {
        $days = (int) $request->input('days', 7);
        $search = $request->input('q');
        $today = Carbon::today();
        $targetDate = $today->copy()->addDays($days);

        $query = PurchaseInvoice::with(['purchaseOrder.supplier', 'payments', 'items'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $targetDate);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder.supplier', fn($s) => $s->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($inv) use ($today) {
                $dueDate = Carbon::parse($inv->due_date);
                $diff = $today->diffInDays($dueDate, false);
                $inv->days_remaining = max(0, $diff);
                return $inv;
            })
            // Filter hanya tagihan yang masih ada sisa outstanding setelah memperhitungkan retur & pembayaran
            ->filter(fn($inv) => $inv->outstanding_amount > 0.01)
            ->values();

        $totalUpcomingAmount = (float) $invoices->sum('outstanding_amount');
        $totalUpcomingCount = $invoices->count();

        return view('accounting.reports.payables-upcoming', compact(
            'invoices', 'days', 'search', 'totalUpcomingAmount', 'totalUpcomingCount'
        ));
    }

    /**
     * 15. Laporan Tren Laba Kotor (Gross Profit Trend & Breakdown)
     */
    public function grossProfit(Request $request): View
    {
        $periodMonths = (int) $request->input('period_months', 12);
        $startDate = Carbon::today()->subMonths($periodMonths)->startOfMonth();

        // Ambil semua item invoice penjualan yang terjadi sejak $startDate
        $items = SalesInvoiceItem::with(['salesInvoice', 'product.productCategory'])
            ->whereHas('salesInvoice', fn($q) => $q->whereDate('invoice_date', '>=', $startDate))
            ->get();

        // Siapkan struktur 12 bulan berurutan
        $monthlyMap = [];
        for ($i = $periodMonths - 1; $i >= 0; $i--) {
            $dt = Carbon::today()->subMonths($i);
            $key = $dt->format('Y-m');
            $monthlyMap[$key] = [
                'month_key'    => $key,
                'label'        => $dt->translatedFormat('M Y'),
                'revenue'      => 0,
                'cogs'         => 0,
                'gross_profit' => 0,
                'margin_pct'   => 0,
            ];
        }

        // Breakdown per Kategori Produk
        $categoryMap = [];

        foreach ($items as $item) {
            $invDate = $item->salesInvoice?->invoice_date;
            if (!$invDate) continue;

            $monthKey = Carbon::parse($invDate)->format('Y-m');

            // Hitung revenue bersih dan COGS bersih setelah memperhitungkan reversed_qty (retur penjualan)
            $qtyInvoiced = max(1, (int) $item->qty_invoiced);
            $reversedQty = min((int) $item->reversed_qty, $qtyInvoiced);
            $effectiveQty = max(0, $qtyInvoiced - $reversedQty);

            $unitPrice = (float) $item->unit_price;
            $unitCogs = (float) ($item->cogs_amount > 0 ? ($item->cogs_amount / $qtyInvoiced) : ($item->product?->purchase_price ?? 0));

            $netRevenue = $effectiveQty * $unitPrice;
            $netCogs = $effectiveQty * $unitCogs;
            $netProfit = $netRevenue - $netCogs;

            // Akumulasi bulanan
            if (isset($monthlyMap[$monthKey])) {
                $monthlyMap[$monthKey]['revenue'] += $netRevenue;
                $monthlyMap[$monthKey]['cogs'] += $netCogs;
                $monthlyMap[$monthKey]['gross_profit'] += $netProfit;
            }

            // Akumulasi kategori
            $catName = $item->product?->productCategory?->name ?? ($item->product?->category ?: 'Lain-lain / Tanpa Kategori');
            if (!isset($categoryMap[$catName])) {
                $categoryMap[$catName] = [
                    'name'         => $catName,
                    'revenue'      => 0,
                    'cogs'         => 0,
                    'gross_profit' => 0,
                    'margin_pct'   => 0,
                ];
            }
            $categoryMap[$catName]['revenue'] += $netRevenue;
            $categoryMap[$catName]['cogs'] += $netCogs;
            $categoryMap[$catName]['gross_profit'] += $netProfit;
        }

        // Hitung margin persentase bulanan
        $monthlyTrend = collect($monthlyMap)->map(function ($m) {
            $m['margin_pct'] = $m['revenue'] > 0 ? round(($m['gross_profit'] / $m['revenue']) * 100, 1) : 0;
            return (object) $m;
        })->values();

        // Hitung margin persentase kategori
        $categoryBreakdown = collect($categoryMap)->map(function ($c) {
            $c['margin_pct'] = $c['revenue'] > 0 ? round(($c['gross_profit'] / $c['revenue']) * 100, 1) : 0;
            return (object) $c;
        })->sortByDesc('gross_profit')->values();

        // Metrik Ringkasan (Executive KPI)
        $totalRevenue     = $monthlyTrend->sum('revenue');
        $totalCogs        = $monthlyTrend->sum('cogs');
        $totalGrossProfit = $monthlyTrend->sum('gross_profit');
        $avgMarginPct     = $totalRevenue > 0 ? round(($totalGrossProfit / $totalRevenue) * 100, 1) : 0;

        return view('accounting.reports.gross-profit', compact(
            'monthlyTrend', 'categoryBreakdown', 'periodMonths',
            'totalRevenue', 'totalCogs', 'totalGrossProfit', 'avgMarginPct'
        ));
    }
}
