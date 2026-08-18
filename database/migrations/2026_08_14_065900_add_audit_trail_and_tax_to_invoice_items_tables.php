<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. purchase_invoice_items
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoice_items', 'goods_receipt_item_id')) {
                $table->foreignId('goods_receipt_item_id')
                    ->nullable()
                    ->after('purchase_order_item_id')
                    ->constrained('goods_receipt_items')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_invoice_items', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('subtotal');
            }
        });

        // 2. sales_invoice_items
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoice_items', 'delivery_item_id')) {
                $table->foreignId('delivery_item_id')
                    ->nullable()
                    ->after('sales_order_item_id')
                    ->constrained('delivery_items')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_invoice_items', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('subtotal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoice_items', 'delivery_item_id')) {
                $table->dropForeign(['delivery_item_id']);
                $table->dropColumn('delivery_item_id');
            }
            if (Schema::hasColumn('sales_invoice_items', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoice_items', 'goods_receipt_item_id')) {
                $table->dropForeign(['goods_receipt_item_id']);
                $table->dropColumn('goods_receipt_item_id');
            }
            if (Schema::hasColumn('purchase_invoice_items', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
        });
    }
};
