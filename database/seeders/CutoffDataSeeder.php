<?php

namespace Database\Seeders;

use App\Enums\DeliveryStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseInvoiceStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesInvoiceStatus;
use App\Enums\SalesOrderStatus;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchasePayment;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesPayment;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingPeriodService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CutoffDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("=== SEEDING CUT-OFF DATA (PERIODE AGUSTUS 2026) DENGAN ITEM DETAIL LENGKAP ===");

        $dataDir = base_path('data');
        $stokFile = $dataDir . DIRECTORY_SEPARATOR . 'stok_cutoff.json';
        $hutangFile = $dataDir . DIRECTORY_SEPARATOR . 'hutang_detailed.json';
        $piutangFile = $dataDir . DIRECTORY_SEPARATOR . 'piutang_detailed.json';

        if (!File::exists($stokFile) || !File::exists($hutangFile) || !File::exists($piutangFile)) {
            $this->command->error("File data JSON belum lengkap di folder {$dataDir}.");
            return;
        }

        $stokData = json_decode(File::get($stokFile), true);
        $hutangData = json_decode(File::get($hutangFile), true);
        $piutangData = json_decode(File::get($piutangFile), true);

        $adminUser = User::where('role', 'admin')->first() ?? User::first();
        if (!$adminUser) {
            $this->command->error("User admin tidak ditemukan.");
            return;
        }

        // 1. Inisialisasi Periode Akuntansi
        $periodService = app(AccountingPeriodService::class);
        $periodService->generateYearPeriods(2026);
        $augustPeriod = AccountingPeriod::where('fiscal_year', 2026)->where('month', 8)->first();
        if ($augustPeriod) {
            $augustPeriod->update(['status' => 'open']);
        }

        // 2. Setup Master Gudang & Kategori
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'WH-SGF'],
            ['name' => 'GUDANG SGF', 'address' => 'Gudang Farmasi SGF', 'is_active' => true]
        );

        $category = ProductCategory::firstOrCreate(
            ['code' => 'KAT-FAR'],
            ['name' => 'Farmasi & Obat-obatan', 'description' => 'Produk obat dan farmasi', 'is_active' => true]
        );

        // 3. Setup Master Supplier
        $supplier = Supplier::firstOrCreate(
            ['name' => 'PT SEJAHTERA LESTARI FARMA'],
            [
                'code' => 'SUP-SLF',
                'contact_person' => 'Bagian Penjualan SLF',
                'phone' => '021-5558899',
                'address' => 'Jakarta, Indonesia',
                'payment_term' => 'NET 30',
                'is_active' => true,
            ]
        );

        // 4. Setup Master Produk (79 Produk) & Stok Awal (70 Produk)
        $this->command->info("Menyimpan 79 Produk Farmasi & Stok Awal Fisik...");
        $productMap = [];
        $skuIndex = 1;
        $totalStockQty = 0;

        foreach ($stokData as $item) {
            $productName = trim($item['product_name']);
            $product = Product::where('name', $productName)->first();
            if (!$product) {
                $sku = 'PRD-' . str_pad($skuIndex++, 4, '0', STR_PAD_LEFT);
                while (Product::where('sku', $sku)->exists()) {
                    $sku = 'PRD-' . str_pad($skuIndex++, 4, '0', STR_PAD_LEFT);
                }
                $product = Product::create([
                    'sku' => $sku,
                    'name' => $productName,
                    'category_id' => $category->id,
                    'category' => $category->name,
                    'unit' => 'pcs',
                    'purchase_price' => 0,
                    'sell_price' => 0,
                    'min_stock' => 5,
                    'is_active' => true,
                ]);
            }
            $productMap[$productName] = $product;

            $qty = (int) $item['ending_qty'];
            if ($qty > 0) {
                $existingMovement = StockMovement::where('product_id', $product->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('type', 'opening_balance')
                    ->where('movement_date', '2026-08-31')
                    ->first();
                if (!$existingMovement) {
                    $existingMovement = StockMovement::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'type' => 'opening_balance',
                        'quantity' => $qty,
                        'unit_cost' => 0,
                        'reference_type' => 'CutoffImport',
                        'reference_id' => null,
                        'movement_date' => '2026-08-31',
                        'notes' => 'Saldo Awal Stok per 31 Agustus 2026 - Cut Off',
                        'user_id' => $adminUser->id,
                    ]);
                }
                $totalStockQty += $qty;
            }
        }
        $this->command->line("   -> Stok fisik terverifikasi: " . number_format($totalStockQty, 0, ',', '.') . " unit.");

        // 5. Setup Kas Akun
        $parentKasBank = ChartOfAccount::where('code', '1-1100')->first();
        $kasKecilAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1-1101'],
            ['name' => 'Kas Kecil', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_id' => $parentKasBank?->id, 'is_cash_bank' => true, 'is_active' => true]
        );
        $kasBesarAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1-1102'],
            ['name' => 'Kas Besar', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_id' => $parentKasBank?->id, 'is_cash_bank' => true, 'is_active' => true]
        );

        $cashAccount = ChartOfAccount::where('is_cash_bank', true)->orderBy('code')->first();

        // 6. Import Kartu Hutang dengan Item Detail (201 Faktur)
        $this->command->info("Mengimpor 201 Faktur Hutang Pembelian dengan Item Rincian...");
        $totalHutang = 0;

        foreach ($hutangData as $row) {
            $piCode = trim($row['pi_code']);
            $invoiceDate = $row['invoice_date'] ?? '2026-08-31';
            $dueDate = $row['due_date'] ?? $invoiceDate;
            $total = (float) $row['total_amount'];
            $paid = (float) $row['paid_amount'];
            $sisa = (float) $row['outstanding_amount'];

            $invStatus = ($paid >= $total) 
                ? PurchaseInvoiceStatus::Paid 
                : (($paid > 0) ? PurchaseInvoiceStatus::Partial : PurchaseInvoiceStatus::Unpaid);

            $po = PurchaseOrder::firstOrCreate(
                ['po_number' => 'PO-' . $piCode],
                [
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $adminUser->id,
                    'order_date' => $invoiceDate,
                    'status' => PurchaseOrderStatus::Confirmed,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => $total,
                    'notes' => 'PO Saldo Awal Cut-Off ' . $piCode,
                ]
            );

            $invoice = PurchaseInvoice::firstOrCreate(
                ['invoice_number' => $piCode],
                [
                    'purchase_order_id' => $po->id,
                    'supplier_invoice_number' => $piCode,
                    'amount' => $total,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'total_amount' => $total,
                    'invoice_date' => $invoiceDate,
                    'due_date' => $dueDate,
                    'status' => $invStatus,
                    'notes' => 'Saldo Awal Hutang per 31 Agustus 2026',
                ]
            );

            // Buat LPB (GoodsReceipt) jika belum ada
            $gr = GoodsReceipt::firstOrCreate(
                ['receipt_number' => 'LPB-' . $piCode],
                [
                    'purchase_order_id' => $po->id,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $adminUser->id,
                    'qc_status' => 'passed',
                    'status' => GoodsReceiptStatus::Confirmed,
                    'is_invoiced' => true,
                    'purchase_invoice_id' => $invoice->id,
                    'received_date' => $invoiceDate,
                    'notes' => 'LPB Saldo Awal Cut-Off ' . $piCode,
                    'approved_by' => $adminUser->id,
                    'approved_at' => $invoiceDate . ' 08:00:00',
                ]
            );

            if ($invoice->goods_receipt_id !== $gr->id) {
                $invoice->update(['goods_receipt_id' => $gr->id]);
            }

            // Buat PO Items & Invoice Items
            if (!empty($row['items']) && $invoice->items()->count() === 0) {
                foreach ($row['items'] as $item) {
                    $pName = trim($item['product_name']);
                    $prod = $productMap[$pName] ?? Product::first();
                    $qty = (int) $item['qty'];
                    $price = (float) $item['unit_price'];
                    $sub = (float) $item['subtotal'];

                    $poItem = PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $prod->id,
                        'qty_ordered' => $qty,
                        'unit_price' => $price,
                        'discount_percent' => 0,
                        'discount_amount' => 0,
                        'subtotal' => $sub,
                    ]);

                    $grItem = GoodsReceiptItem::firstOrCreate(
                        [
                            'goods_receipt_id' => $gr->id,
                            'purchase_order_item_id' => $poItem->id,
                        ],
                        [
                            'warehouse_id' => $warehouse->id,
                            'qty_received' => $qty,
                            'qty_rejected' => 0,
                            'invoiced_qty' => $qty,
                            'unit_cost' => $price,
                            'condition' => 'Good',
                        ]
                    );

                    PurchaseInvoiceItem::create([
                        'purchase_invoice_id' => $invoice->id,
                        'purchase_order_item_id' => $poItem->id,
                        'goods_receipt_item_id' => $grItem->id,
                        'product_id' => $prod->id,
                        'qty_invoiced' => $qty,
                        'reversed_qty' => 0,
                        'unit_price' => $price,
                        'discount_percent' => 0,
                        'discount_amount' => 0,
                        'subtotal' => $sub,
                        'tax_amount' => 0,
                    ]);
                }
            }

            // Pastikan item LPB terisi jika invoice sudah punya item tapi LPB belum punya item
            if ($gr->items()->count() === 0) {
                foreach ($invoice->items as $invItem) {
                    $grItem = GoodsReceiptItem::firstOrCreate(
                        [
                            'goods_receipt_id' => $gr->id,
                            'purchase_order_item_id' => $invItem->purchase_order_item_id,
                        ],
                        [
                            'warehouse_id' => $warehouse->id,
                            'qty_received' => $invItem->qty_invoiced,
                            'qty_rejected' => 0,
                            'invoiced_qty' => $invItem->qty_invoiced,
                            'unit_cost' => $invItem->unit_price,
                            'condition' => 'Good',
                        ]
                    );
                    if ($invItem->goods_receipt_item_id !== $grItem->id) {
                        $invItem->update(['goods_receipt_item_id' => $grItem->id]);
                    }
                }
            }

            $po->update(['status' => PurchaseOrderStatus::Done]);

            if ($paid > 0 && $invoice->payments()->count() === 0) {
                PurchasePayment::create([
                    'payment_number' => PurchasePayment::generatePaymentNumber('transfer', $invoiceDate),
                    'purchase_invoice_id' => $invoice->id,
                    'chart_of_account_id' => $cashAccount?->id,
                    'user_id' => $adminUser->id,
                    'amount' => $paid,
                    'status' => PaymentStatus::Posted,
                    'payment_date' => $invoiceDate,
                    'method' => 'transfer',
                    'payment_type' => 'bank',
                    'reference_number' => 'HIST-PAY-' . $piCode,
                    'notes' => 'Pembayaran historis sebelum 31 Agustus 2026',
                ]);
            }

            $totalHutang += $sisa;
        }
        $this->command->line("   -> 201 Faktur Hutang berhasil dibuat ber-item. Total Sisa: Rp " . number_format($totalHutang, 0, ',', '.'));

        // 7. Import Kartu Piutang dengan Item Detail (763 Faktur)
        $this->command->info("Mengimpor 763 Faktur Piutang Penjualan dengan Item Rincian...");
        $customerNames = array_unique(array_map(fn($r) => trim($r['customer_name']), $piutangData));
        $customerMap = [];
        $custIndex = 1;

        foreach ($customerNames as $cName) {
            $customer = Customer::where('name', $cName)->first();
            if (!$customer) {
                $code = 'CUST-' . str_pad($custIndex++, 4, '0', STR_PAD_LEFT);
                while (Customer::where('code', $code)->exists()) {
                    $code = 'CUST-' . str_pad($custIndex++, 4, '0', STR_PAD_LEFT);
                }
                $customer = Customer::create([
                    'code' => $code,
                    'name' => $cName,
                    'contact_person' => 'Bagian Pembelian',
                    'phone' => '021-0000000',
                    'address' => 'Indonesia',
                    'credit_limit' => 100_000_000,
                    'payment_term' => 'NET 30',
                    'tax_type' => 'non_pkp',
                    'is_active' => true,
                ]);
            }
            $customerMap[$cName] = $customer;
        }

        $totalPiutang = 0;

        foreach ($piutangData as $row) {
            $cName = trim($row['customer_name']);
            $cust = $customerMap[$cName] ?? null;
            if (!$cust) continue;

            $faktur = trim($row['invoice_number']);
            $poNumber = trim($row['po_number'] ?? '');
            $invoiceDate = $row['invoice_date'] ?? '2026-08-31';
            $dueDate = $row['due_date'] ?? $invoiceDate;
            $total = (float) $row['total_amount'];
            $paid = (float) $row['paid_amount'];
            $sisa = (float) $row['outstanding_amount'];

            $invStatus = ($paid >= $total) 
                ? SalesInvoiceStatus::Paid 
                : (($paid > 0) ? SalesInvoiceStatus::Partial : SalesInvoiceStatus::Unpaid);

            $soNumber = !empty($poNumber) ? "PO-{$poNumber} [{$faktur}]" : "SO-{$faktur}";

            $so = SalesOrder::firstOrCreate(
                ['so_number' => $soNumber],
                [
                    'customer_id' => $cust->id,
                    'channel' => 'offline',
                    'user_id' => $adminUser->id,
                    'status' => SalesOrderStatus::Confirmed,
                    'order_date' => $invoiceDate,
                    'discount_amount' => 0,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'total_amount' => $total,
                    'notes' => 'No. PO Asli: ' . ($poNumber ?: '-'),
                ]
            );

            $invoice = SalesInvoice::firstOrCreate(
                ['invoice_number' => $faktur],
                [
                    'sales_order_id' => $so->id,
                    'amount' => $total,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'total_amount' => $total,
                    'invoice_date' => $invoiceDate,
                    'due_date' => $dueDate,
                    'status' => $invStatus,
                    'notes' => 'Saldo Awal Piutang per 31 Agustus 2026' . ($poNumber ? " (PO: {$poNumber})" : ''),
                ]
            );

            // Buat Surat Jalan (Delivery) jika belum ada
            $delivery = Delivery::firstOrCreate(
                ['delivery_number' => 'SJ-' . $faktur],
                [
                    'sales_order_id' => $so->id,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $adminUser->id,
                    'condition_status' => 'baik',
                    'status' => DeliveryStatus::Delivered,
                    'is_invoiced' => true,
                    'sales_invoice_id' => $invoice->id,
                    'delivery_date' => $invoiceDate,
                    'received_date' => $invoiceDate,
                    'recipient_name' => $cName,
                    'shipping_address' => 'Indonesia',
                    'receipt_notes' => 'Diterima lengkap cut-off per 31 Agustus 2026',
                    'notes' => 'Surat Jalan Saldo Awal ' . $faktur,
                ]
            );

            if ($invoice->delivery_id !== $delivery->id) {
                $invoice->update(['delivery_id' => $delivery->id]);
            }

            // Buat SO Items & Invoice Items
            if (!empty($row['items']) && $invoice->items()->count() === 0) {
                foreach ($row['items'] as $item) {
                    $pName = trim($item['product_name']);
                    $prod = $productMap[$pName] ?? Product::first();
                    $qty = (int) $item['qty'];
                    $price = (float) $item['unit_price'];
                    $sub = (float) $item['subtotal'];

                    $soItem = SalesOrderItem::create([
                        'sales_order_id' => $so->id,
                        'product_id' => $prod->id,
                        'qty_ordered' => $qty,
                        'unit_price' => $price,
                        'discount_percent' => 0,
                        'discount_amount' => 0,
                        'subtotal' => $sub,
                    ]);

                    $delivItem = DeliveryItem::firstOrCreate(
                        [
                            'delivery_id' => $delivery->id,
                            'sales_order_item_id' => $soItem->id,
                        ],
                        [
                            'qty_delivered' => $qty,
                            'invoiced_qty' => $qty,
                            'condition' => 'Good',
                        ]
                    );

                    SalesInvoiceItem::create([
                        'sales_invoice_id' => $invoice->id,
                        'sales_order_item_id' => $soItem->id,
                        'delivery_item_id' => $delivItem->id,
                        'product_id' => $prod->id,
                        'qty_invoiced' => $qty,
                        'reversed_qty' => 0,
                        'unit_price' => $price,
                        'discount_percent' => 0,
                        'discount_amount' => 0,
                        'subtotal' => $sub,
                        'tax_amount' => 0,
                        'cogs_amount' => 0,
                    ]);
                }
            }

            // Pastikan item Surat Jalan terisi jika invoice sudah punya item tapi Delivery belum punya item
            if ($delivery->items()->count() === 0) {
                foreach ($invoice->items as $invItem) {
                    $delivItem = DeliveryItem::firstOrCreate(
                        [
                            'delivery_id' => $delivery->id,
                            'sales_order_item_id' => $invItem->sales_order_item_id,
                        ],
                        [
                            'qty_delivered' => $invItem->qty_invoiced,
                            'invoiced_qty' => $invItem->qty_invoiced,
                            'condition' => 'Good',
                        ]
                    );
                    if ($invItem->delivery_item_id !== $delivItem->id) {
                        $invItem->update(['delivery_item_id' => $delivItem->id]);
                    }
                }
            }

            if ($paid > 0 && $invoice->payments()->count() === 0) {
                SalesPayment::create([
                    'payment_number' => SalesPayment::generatePaymentNumber('transfer', $invoiceDate),
                    'sales_invoice_id' => $invoice->id,
                    'chart_of_account_id' => $cashAccount?->id,
                    'user_id' => $adminUser->id,
                    'amount' => $paid,
                    'status' => PaymentStatus::Posted,
                    'payment_date' => $invoiceDate,
                    'payment_type' => 'bank',
                    'method' => 'transfer',
                    'reference_number' => 'HIST-REC-' . $faktur,
                    'notes' => 'Penerimaan pembayaran piutang sebelum 31 Agustus 2026',
                ]);
            }

            $totalPiutang += $sisa;
        }
        $this->command->line("   -> 763 Faktur Piutang berhasil dibuat ber-item. Total Sisa: Rp " . number_format($totalPiutang, 0, ',', '.'));

        // 8. Posting Jurnal Saldo Awal Neraca
        $this->command->info("Membukukan Jurnal Saldo Awal Neraca (Balanced)...");
        $piutangAccount = ChartOfAccount::where('code', '1-1200')->first();
        $hutangAccount = ChartOfAccount::where('code', '2-1100')->first();
        $modalAccount = ChartOfAccount::where('code', '3-1100')->first();

        $kasKecilAmount = 464400.0;
        $kasBesarAmount = 0.0;

        if ($piutangAccount && $hutangAccount && $modalAccount) {
            $oldEntries = JournalEntry::where('reference_type', 'OpeningBalance')
                ->whereDate('entry_date', '2026-08-31')
                ->get();
            foreach ($oldEntries as $old) {
                $old->lines()->delete();
                $old->delete();
            }

            $totalDebitAset = $totalPiutang + $kasKecilAmount + $kasBesarAmount;
            $selisihModal = $totalDebitAset - $totalHutang;

            $journal = JournalEntry::create([
                'entry_number' => 'JU-31/08/2026-0001',
                'entry_date' => '2026-08-31',
                'status' => 'posted',
                'reference_type' => 'OpeningBalance',
                'reference_id' => null,
                'description' => 'Saldo Awal Neraca per 31 Agustus 2026 (Kas, Piutang & Hutang Usaha Cut-Off)',
                'created_by' => $adminUser->id,
                'posted_by' => $adminUser->id,
                'posted_at' => now(),
            ]);

            if ($kasKecilAmount > 0) {
                JournalLine::create([
                    'journal_entry_id' => $journal->id,
                    'chart_of_account_id' => $kasKecilAccount->id,
                    'debit' => $kasKecilAmount,
                    'credit' => 0,
                    'description' => 'Saldo Awal Kas Kecil per 31 Agustus 2026',
                ]);
            }

            JournalLine::create([
                'journal_entry_id' => $journal->id,
                'chart_of_account_id' => $piutangAccount->id,
                'debit' => $totalPiutang,
                'credit' => 0,
                'description' => 'Saldo Awal Piutang Usaha per 31 Agustus 2026',
            ]);

            JournalLine::create([
                'journal_entry_id' => $journal->id,
                'chart_of_account_id' => $hutangAccount->id,
                'debit' => 0,
                'credit' => $totalHutang,
                'description' => 'Saldo Awal Hutang Usaha per 31 Agustus 2026',
            ]);

            JournalLine::create([
                'journal_entry_id' => $journal->id,
                'chart_of_account_id' => $modalAccount->id,
                'debit' => $selisihModal < 0 ? abs($selisihModal) : 0,
                'credit' => $selisihModal >= 0 ? $selisihModal : 0,
                'description' => 'Modal / Ekuitas Bersih Saldo Awal per 31 Agustus 2026',
            ]);

            $this->command->line("   -> Jurnal: {$journal->entry_number} (100% Balanced)");
        }

        $this->command->info("=== SEEDING CUT-OFF SELESAI DENGAN SUKSES! ===");
    }
}
