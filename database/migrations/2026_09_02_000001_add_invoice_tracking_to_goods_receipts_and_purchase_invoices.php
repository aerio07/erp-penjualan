<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom tracking invoice di goods_receipts (level header LPB)
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipts', 'is_invoiced')) {
                $table->boolean('is_invoiced')->default(false)->after('qc_status');
            }
            if (!Schema::hasColumn('goods_receipts', 'purchase_invoice_id')) {
                $table->foreignId('purchase_invoice_id')->nullable()->after('is_invoiced')
                    ->constrained('purchase_invoices')->nullOnDelete();
            }
        });

        // 2. Tambah kolom goods_receipt_id di purchase_invoices (referensi langsung ke LPB sumber)
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'goods_receipt_id')) {
                $table->foreignId('goods_receipt_id')->nullable()->after('purchase_order_id')
                    ->constrained('goods_receipts')->nullOnDelete();
            }
        });

        // 3. Migrasi data lama: tandai GRN yang sudah pernah dipakai invoice sebagai is_invoiced = true
        //    Ini mencegah GRN lama (pola Opsi A) dipakai ulang untuk invoice baru
        $invoicedGrnIds = DB::table('purchase_invoice_items')
            ->whereNotNull('goods_receipt_item_id')
            ->join('goods_receipt_items', 'purchase_invoice_items.goods_receipt_item_id', '=', 'goods_receipt_items.id')
            ->distinct()
            ->pluck('goods_receipt_items.goods_receipt_id');

        if ($invoicedGrnIds->isNotEmpty()) {
            DB::table('goods_receipts')
                ->whereIn('id', $invoicedGrnIds)
                ->update(['is_invoiced' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'goods_receipt_id')) {
                $table->dropForeign(['goods_receipt_id']);
                $table->dropColumn('goods_receipt_id');
            }
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipts', 'purchase_invoice_id')) {
                $table->dropForeign(['purchase_invoice_id']);
                $table->dropColumn('purchase_invoice_id');
            }
            if (Schema::hasColumn('goods_receipts', 'is_invoiced')) {
                $table->dropColumn('is_invoiced');
            }
        });
    }
};
