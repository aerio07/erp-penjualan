<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesPayment;
use App\Models\SalesReturn;
use App\Models\StockDisposition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JournalService
{
    /**
     * Buat journal entry dari purchase invoice.
     * Debit: Persediaan | Credit: Hutang Usaha
     */
    public function createFromPurchaseInvoice(PurchaseInvoice $invoice): JournalEntry
    {
        $persediaan = $this->findAccount('1-1400'); // Persediaan Barang
        $ppnMasukan = $this->findAccount('1-1500'); // PPN Masukan
        $hutang     = $this->findAccount('2-1100'); // Hutang Usaha

        return $this->createEntry(
            date: $invoice->invoice_date->toDateString(),
            description: "Invoice Pembelian #{$invoice->invoice_number}",
            referenceType: PurchaseInvoice::class,
            referenceId: $invoice->id,
            lines: [
                ['account_id' => $persediaan->id, 'debit' => $invoice->amount,     'credit' => 0, 'description' => 'Nilai Persediaan'],
                ['account_id' => $ppnMasukan->id, 'debit' => $invoice->tax_amount, 'credit' => 0, 'description' => 'PPN Masukan'],
                ['account_id' => $hutang->id,     'debit' => 0,                    'credit' => $invoice->total_amount, 'description' => 'Hutang ke Supplier'],
            ]
        );
    }

    /**
     * Buat journal entry dari purchase payment.
     * Debit: Hutang Usaha | Credit: Kas/Bank
     */
    public function createFromPurchasePayment(PurchasePayment $payment): JournalEntry
    {
        $hutang = $this->findAccount('2-1100');
        $kas    = $this->findAccount('1-1100'); // Kas / Bank

        return $this->createEntry(
            date: $payment->payment_date->toDateString(),
            description: "Pembayaran Hutang #{$payment->purchaseInvoice->invoice_number}",
            referenceType: PurchasePayment::class,
            referenceId: $payment->id,
            lines: [
                ['account_id' => $hutang->id, 'debit' => $payment->amount, 'credit' => 0,              'description' => 'Pelunasan Hutang'],
                ['account_id' => $kas->id,    'debit' => 0,                'credit' => $payment->amount, 'description' => 'Kas Keluar'],
            ]
        );
    }

    /**
     * Buat journal entry dari sales invoice.
     * Debit: Piutang Usaha | Credit: Penjualan
     * Debit: HPP          | Credit: Persediaan
     */
    public function createFromSalesInvoice(SalesInvoice $invoice, float $cogs): JournalEntry
    {
        $piutang    = $this->findAccount('1-1200'); // Piutang Usaha
        $penjualan  = $this->findAccount('4-1100'); // Penjualan
        $ppnKeluaran = $this->findAccount('2-1400'); // PPN Keluaran
        $hpp        = $this->findAccount('5-1100'); // HPP / COGS
        $persediaan = $this->findAccount('1-1400');

        return $this->createEntry(
            date: $invoice->invoice_date->toDateString(),
            description: "Invoice Penjualan #{$invoice->invoice_number}",
            referenceType: SalesInvoice::class,
            referenceId: $invoice->id,
            lines: [
                ['account_id' => $piutang->id,    'debit' => $invoice->total_amount, 'credit' => 0,                      'description' => 'Piutang Customer'],
                ['account_id' => $penjualan->id,  'debit' => 0,                      'credit' => $invoice->amount,        'description' => 'Pendapatan Penjualan'],
                ['account_id' => $ppnKeluaran->id, 'debit' => 0,                     'credit' => $invoice->tax_amount,    'description' => 'PPN Keluaran'],
                ['account_id' => $hpp->id,        'debit' => $cogs,                  'credit' => 0,                      'description' => 'Harga Pokok Penjualan'],
                ['account_id' => $persediaan->id, 'debit' => 0,                      'credit' => $cogs,                  'description' => 'Pengurangan Persediaan'],
            ]
        );
    }

    /**
     * Buat journal entry dari sales payment.
     * Debit: Kas/Bank | Credit: Piutang Usaha
     */
    public function createFromSalesPayment(SalesPayment $payment): JournalEntry
    {
        $kas     = $this->findAccount('1-1100');
        $piutang = $this->findAccount('1-1200');

        return $this->createEntry(
            date: $payment->payment_date->toDateString(),
            description: "Penerimaan Pembayaran #{$payment->salesInvoice->invoice_number}",
            referenceType: SalesPayment::class,
            referenceId: $payment->id,
            lines: [
                ['account_id' => $kas->id,     'debit' => $payment->amount, 'credit' => 0,               'description' => 'Kas Masuk'],
                ['account_id' => $piutang->id, 'debit' => 0,                'credit' => $payment->amount, 'description' => 'Pelunasan Piutang'],
            ]
        );
    }

    /**
     * Buat journal entry dari retur pembelian (Purchase Return).
     *
     * Logika:
     * - Per item retur: lookup SEMUA invoice items terkait GRN item ybs (->get(), bukan ->first())
     * - qty_to_reverse = min(qty_returned, total_invoiced - total_reversed)  → anti double-reversal
     * - Nominal dari stored subtotal/tax_amount (weighted average) → sudah termasuk prorate diskon header
     * - reversed_qty di-increment FIFO per invoice item
     * - Item 'accepted' → kredit Persediaan, item 'rejected' → kredit Retur Pembelian
     * - Idempotency: skip jika JournalEntry sudah pernah dibuat untuk retur ini
     * - Semua angka di-round(2) untuk menghindari akumulasi selisih pembulatan
     *
     * Debit : Hutang Usaha (2-1100)
     * Kredit: PPN Masukan (1-1500)
     * Kredit: Persediaan (1-1400) — jika source_type='accepted'
     * Kredit: Retur Pembelian (5-1200) — jika source_type='rejected'
     */
    public function createFromPurchaseReturn(PurchaseReturn $return): ?JournalEntry
    {
        // Idempotency guard — cegah jurnal dobel jika complete() ke-trigger dua kali
        if (JournalEntry::where('reference_type', PurchaseReturn::class)
            ->where('reference_id', $return->id)->exists()) {
            return null;
        }

        $return->load('items');

        $totalHutang     = 0;
        $totalPpnMasukan = 0;
        $totalPersediaan = 0;
        $totalReturBeli  = 0;

        foreach ($return->items as $item) {
            // Aggregate SEMUA invoice items yang merujuk goods_receipt_item_id ini
            $invoiceItems = PurchaseInvoiceItem::where('goods_receipt_item_id', $item->goods_receipt_item_id)
                ->get();

            if ($invoiceItems->isEmpty()) {
                continue; // Belum pernah di-invoice — tidak ada hutang untuk dibalik
            }

            $totalInvoicedQty = $invoiceItems->sum('qty_invoiced');
            $totalReversedQty = $invoiceItems->sum('reversed_qty');

            // Cap ke sisa yang belum pernah dibalik jurnalnya
            $qtyToReverse = min($item->qty, $totalInvoicedQty - $totalReversedQty);
            if ($qtyToReverse <= 0) {
                continue;
            }

            // Weighted average dari stored subtotal & tax_amount
            // (sudah termasuk diskon item + prorate diskon header + rounding)
            $totalSubtotalAll = (float) $invoiceItems->sum('subtotal');
            $totalTaxAll      = (float) $invoiceItems->sum('tax_amount');
            $totalQtyAll      = $totalInvoicedQty;

            $unitNet = $totalQtyAll > 0 ? ($totalSubtotalAll / $totalQtyAll) : 0;
            $unitTax = $totalQtyAll > 0 ? ($totalTaxAll / $totalQtyAll) : 0;

            $netLine   = round($qtyToReverse * $unitNet, 2);
            $lineTax   = round($qtyToReverse * $unitTax, 2);
            $lineTotal = round($netLine + $lineTax, 2);

            // Increment reversed_qty secara FIFO di tiap invoice item
            $remaining = $qtyToReverse;
            foreach ($invoiceItems as $invItem) {
                $canReverse = $invItem->qty_invoiced - $invItem->reversed_qty;
                if ($canReverse <= 0) continue;
                $reverseThis = min($remaining, $canReverse);
                $invItem->increment('reversed_qty', $reverseThis);
                $remaining -= $reverseThis;
                if ($remaining <= 0) break;
            }

            // Akumulasi per source_type
            $totalHutang     += $lineTotal;
            $totalPpnMasukan += $lineTax;

            if ($item->source_type === 'accepted') {
                $totalPersediaan += $netLine; // Barang pernah masuk stok → kredit Persediaan
            } else {
                $totalReturBeli += $netLine;  // Barang reject → kredit Retur Pembelian
            }
        }

        if ($totalHutang == 0) {
            return null; // Semua baris di-skip (belum di-invoice atau sudah dibalik penuh)
        }

        $hutang     = $this->findAccount('2-1100');
        $ppnMasukan = $this->findAccount('1-1500');
        $persediaan = $this->findAccount('1-1400');
        $returBeli  = $this->findAccount('5-1200');

        $lines = [
            ['account_id' => $hutang->id, 'debit' => $totalHutang, 'credit' => 0, 'description' => 'Pembalikan Hutang Usaha (Retur Pembelian)'],
            ['account_id' => $ppnMasukan->id, 'debit' => 0, 'credit' => $totalPpnMasukan, 'description' => 'Pembalikan PPN Masukan'],
        ];

        if ($totalPersediaan > 0) {
            $lines[] = ['account_id' => $persediaan->id, 'debit' => 0, 'credit' => $totalPersediaan, 'description' => 'Pengurangan Persediaan (Barang Keluar via Retur)'];
        }
        if ($totalReturBeli > 0) {
            $lines[] = ['account_id' => $returBeli->id, 'debit' => 0, 'credit' => $totalReturBeli, 'description' => 'Retur Pembelian (Barang Reject)'];
        }

        return $this->createEntry(
            date: $return->return_date->toDateString(),
            description: "Retur Pembelian #{$return->return_number}",
            referenceType: PurchaseReturn::class,
            referenceId: $return->id,
            lines: $lines
        );
    }

    /**
     * Buat journal entry dari retur penjualan (Sales Return).
     *
     * Logika:
     * - Per item retur: lookup SEMUA invoice items terkait delivery_item_id ybs
     * - qty_to_reverse = min(qty_returned, total_invoiced - total_reversed)
     * - DPP & PPN dari stored subtotal/tax_amount (weighted average)
     * - COGS dari stored cogs_amount (snapshot, proporsional)
     * - Kondisi 'baik' → debet Persediaan + kredit HPP (barang masuk stok sellable)
     * - Kondisi 'rusak' → skip sisi Persediaan & HPP (barang masuk karantina)
     * - Idempotency + anti double-reversal + round(2)
     *
     * Debit : Retur Penjualan (4-1200) + PPN Keluaran (2-1400) [+ Persediaan jika baik]
     * Kredit: Piutang Usaha (1-1200) [+ HPP jika baik]
     */
    public function createFromSalesReturn(SalesReturn $return): ?JournalEntry
    {
        // Idempotency guard
        if (JournalEntry::where('reference_type', SalesReturn::class)
            ->where('reference_id', $return->id)->exists()) {
            return null;
        }

        $return->load('items.product');

        $totalReturJual   = 0;
        $totalPpnKeluaran = 0;
        $totalPiutang     = 0;
        $totalPersediaan  = 0;
        $totalHpp         = 0;

        foreach ($return->items as $item) {
            // Aggregate SEMUA invoice items yang merujuk delivery_item_id ini
            $invoiceItems = SalesInvoiceItem::where('delivery_item_id', $item->delivery_item_id)
                ->get();

            if ($invoiceItems->isEmpty()) {
                continue; // Belum pernah di-invoice
            }

            $totalInvoicedQty = $invoiceItems->sum('qty_invoiced');
            $totalReversedQty = $invoiceItems->sum('reversed_qty');

            $qtyToReverse = min($item->qty, $totalInvoicedQty - $totalReversedQty);
            if ($qtyToReverse <= 0) {
                continue;
            }

            // Weighted average dari stored subtotal & tax_amount
            $totalSubtotalAll = (float) $invoiceItems->sum('subtotal');
            $totalTaxAll      = (float) $invoiceItems->sum('tax_amount');
            $totalQtyAll      = $totalInvoicedQty;

            $unitNet = $totalQtyAll > 0 ? ($totalSubtotalAll / $totalQtyAll) : 0;
            $unitTax = $totalQtyAll > 0 ? ($totalTaxAll / $totalQtyAll) : 0;

            $netLine   = round($qtyToReverse * $unitNet, 2);
            $lineTax   = round($qtyToReverse * $unitTax, 2);
            $lineTotal = round($netLine + $lineTax, 2);

            // COGS dari snapshot (proporsional)
            $totalCogsSnapshot = (float) $invoiceItems->sum('cogs_amount');
            $cogsPerUnit       = $totalQtyAll > 0 ? ($totalCogsSnapshot / $totalQtyAll) : 0;
            $cogsLine          = round($qtyToReverse * $cogsPerUnit, 2);

            // Increment reversed_qty FIFO
            $remaining = $qtyToReverse;
            foreach ($invoiceItems as $invItem) {
                $canReverse = $invItem->qty_invoiced - $invItem->reversed_qty;
                if ($canReverse <= 0) continue;
                $reverseThis = min($remaining, $canReverse);
                $invItem->increment('reversed_qty', $reverseThis);
                $remaining -= $reverseThis;
                if ($remaining <= 0) break;
            }

            // Akumulasi
            $totalReturJual   += $netLine;
            $totalPpnKeluaran += $lineTax;
            $totalPiutang     += $lineTotal;

            if ($item->condition === 'baik') {
                $totalPersediaan += $cogsLine; // Barang masuk kembali stok sellable
                $totalHpp        += $cogsLine; // Membalik beban HPP
            }
            // Kondisi 'rusak' → barang masuk karantina, TIDAK ada pembalikan Persediaan/HPP
        }

        if ($totalPiutang == 0) {
            return null;
        }

        $returJual   = $this->findAccount('4-1200');
        $ppnKeluaran = $this->findAccount('2-1400');
        $piutang     = $this->findAccount('1-1200');
        $persediaan  = $this->findAccount('1-1400');
        $hpp         = $this->findAccount('5-1100');

        $lines = [
            ['account_id' => $returJual->id,   'debit' => $totalReturJual,   'credit' => 0, 'description' => 'Retur Penjualan'],
            ['account_id' => $ppnKeluaran->id, 'debit' => $totalPpnKeluaran, 'credit' => 0, 'description' => 'Pembalikan PPN Keluaran'],
            ['account_id' => $piutang->id,     'debit' => 0, 'credit' => $totalPiutang,     'description' => 'Pengurangan Piutang Usaha'],
        ];

        if ($totalPersediaan > 0) {
            $lines[] = ['account_id' => $persediaan->id, 'debit' => $totalPersediaan, 'credit' => 0, 'description' => 'Pengembalian Persediaan (Barang Baik)'];
        }
        if ($totalHpp > 0) {
            $lines[] = ['account_id' => $hpp->id, 'debit' => 0, 'credit' => $totalHpp, 'description' => 'Pembalikan HPP'];
        }

        return $this->createEntry(
            date: $return->return_date->toDateString(),
            description: "Retur Penjualan #{$return->return_number}",
            referenceType: SalesReturn::class,
            referenceId: $return->id,
            lines: $lines
        );
    }

    /**
     * Buat journal entry dari penyelesaian barang karantina (Stock Disposition).
     *
     * Logika:
     * - write_off: Debet Kerugian Persediaan Rusak (5-1300), Kredit Persediaan (1-1400)
     * - sold_as_reject: Debet Kas (1-1100), Kredit Pendapatan Penjualan Reject (4-1400),
     *                   Debet HPP Penjualan Reject (5-1400), Kredit Persediaan (1-1400)
     */
    public function createFromStockDisposition(StockDisposition $disposition): JournalEntry
    {
        $existing = JournalEntry::where('reference_type', StockDisposition::class)
            ->where('reference_id', $disposition->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $disposition->load(['product', 'warehouse']);

        $totalCost  = round((float) $disposition->qty * (float) $disposition->unit_cost, 2);
        $persediaan = $this->findAccount('1-1400'); // Persediaan Barang

        if ($disposition->resolution_type === 'write_off') {
            $kerugian = $this->findAccount('5-1300'); // Kerugian Persediaan Rusak

            $lines = [
                ['account_id' => $kerugian->id,   'debit' => $totalCost, 'credit' => 0,          'description' => "Penghapusan Stok Rusak {$disposition->product->name}"],
                ['account_id' => $persediaan->id, 'debit' => 0,          'credit' => $totalCost, 'description' => "Pengurangan Persediaan (Write Off)"],
            ];
            $desc = "Write-off Stok Rusak #{$disposition->disposition_number}";
        } else {
            $saleAmount = round((float) $disposition->qty * (float) ($disposition->sale_price ?? 0), 2);
            $kas        = $this->findAccount('1-1100'); // Kas / Bank
            $pendapatan = $this->findAccount('4-1400'); // Pendapatan Penjualan Reject
            $hppReject  = $this->findAccount('5-1400'); // HPP Penjualan Reject

            $lines = [
                ['account_id' => $kas->id,        'debit' => $saleAmount, 'credit' => 0,          'description' => "Penerimaan Kas Penjualan Reject {$disposition->product->name}"],
                ['account_id' => $pendapatan->id, 'debit' => 0,           'credit' => $saleAmount, 'description' => "Pendapatan Penjualan Reject"],
                ['account_id' => $hppReject->id,  'debit' => $totalCost,  'credit' => 0,          'description' => "HPP Penjualan Reject"],
                ['account_id' => $persediaan->id, 'debit' => 0,           'credit' => $totalCost,  'description' => "Pengurangan Persediaan (Sold as Reject)"],
            ];
            $desc = "Penjualan Barang Reject #{$disposition->disposition_number}";
        }

        $entry = $this->createEntry(
            date: $disposition->disposed_at->toDateString(),
            description: $desc,
            referenceType: StockDisposition::class,
            referenceId: $disposition->id,
            lines: $lines
        );

        return $this->postEntry($entry);
    }

    /**
     * Helper: buat JournalEntry beserta JournalLines dalam satu transaksi DB.
     */
    private function createEntry(
        string $date,
        string $description,
        string $referenceType,
        int $referenceId,
        array $lines
    ): JournalEntry {
        return DB::transaction(function () use ($date, $description, $referenceType, $referenceId, $lines) {
            $entry = JournalEntry::create([
                'entry_number'   => $this->generateEntryNumber(),
                'entry_date'     => $date,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'description'    => $description,
                'status'         => 'draft',
                'created_by'     => Auth::id() ?? 1,
            ]);

            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id'    => $entry->id,
                    'chart_of_account_id' => $line['account_id'],
                    'debit'               => $line['debit'],
                    'credit'              => $line['credit'],
                    'description'         => $line['description'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Post journal entry (draft → posted).
     */
    public function postEntry(JournalEntry $entry): JournalEntry
    {
        if (!$entry->isBalanced()) {
            throw new \RuntimeException("Journal entry #{$entry->entry_number} tidak balance!");
        }

        $entry->update([
            'status'    => 'posted',
            'posted_by' => Auth::id(),
            'posted_at' => now(),
        ]);

        return $entry->fresh();
    }

    private function findAccount(string $code): ChartOfAccount
    {
        $account = ChartOfAccount::where('code', $code)->first();

        if (!$account) {
            throw new \RuntimeException("Chart of Account dengan kode '{$code}' tidak ditemukan. Pastikan seeder sudah dijalankan.");
        }

        return $account;
    }

    private function generateEntryNumber(): string
    {
        $prefix = 'JE-' . date('Ym') . '-';
        $last   = JournalEntry::where('entry_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('entry_number');

        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}

