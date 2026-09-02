<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan kolom tracking invoice pada deliveries (Surat Jalan)
        Schema::table('deliveries', function (Blueprint $table) {
            $table->boolean('is_invoiced')->default(false)->after('condition_status');
            $table->foreignId('sales_invoice_id')
                ->nullable()
                ->after('is_invoiced')
                ->constrained('sales_invoices')
                ->nullOnDelete();
        });

        // 2. Tambahkan foreign key delivery_id pada sales_invoices
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('delivery_id')
                ->nullable()
                ->after('sales_order_id')
                ->constrained('deliveries')
                ->nullOnDelete();
        });

        // 3. Data Migration untuk data lama yang sudah pernah di-invoice
        // Cari delivery_id yang sudah pernah memiliki invoice item atau invoiced_qty > 0
        $invoicedDeliveryIds = DB::table('sales_invoice_items')
            ->join('delivery_items', 'sales_invoice_items.delivery_item_id', '=', 'delivery_items.id')
            ->pluck('delivery_items.delivery_id')
            ->unique()
            ->filter();

        // Cari juga delivery_items yang invoiced_qty > 0
        $deliveryIdsWithInvoicedQty = DB::table('delivery_items')
            ->where('invoiced_qty', '>', 0)
            ->pluck('delivery_id')
            ->unique()
            ->filter();

        $allInvoicedDeliveries = $invoicedDeliveryIds->merge($deliveryIdsWithInvoicedQty)->unique();

        foreach ($allInvoicedDeliveries as $deliveryId) {
            // Cari invoice id terkait jika ada
            $invoiceId = DB::table('sales_invoice_items')
                ->join('delivery_items', 'sales_invoice_items.delivery_item_id', '=', 'delivery_items.id')
                ->where('delivery_items.delivery_id', $deliveryId)
                ->value('sales_invoice_items.sales_invoice_id');

            DB::table('deliveries')
                ->where('id', $deliveryId)
                ->update([
                    'is_invoiced' => true,
                    'sales_invoice_id' => $invoiceId,
                ]);

            if ($invoiceId) {
                DB::table('sales_invoices')
                    ->where('id', $invoiceId)
                    ->whereNull('delivery_id')
                    ->update(['delivery_id' => $deliveryId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_id');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_invoice_id');
            $table->dropColumn('is_invoiced');
        });
    }
};
