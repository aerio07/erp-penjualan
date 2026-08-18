<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoice_items', 'reversed_qty')) {
                $table->integer('reversed_qty')->default(0)->after('qty_invoiced')
                    ->comment('qty yang sudah dibalik jurnalnya via retur');
            }
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoice_items', 'reversed_qty')) {
                $table->integer('reversed_qty')->default(0)->after('qty_invoiced')
                    ->comment('qty yang sudah dibalik jurnalnya via retur');
            }
            if (!Schema::hasColumn('sales_invoice_items', 'cogs_amount')) {
                $table->decimal('cogs_amount', 15, 2)->default(0)->after('tax_amount')
                    ->comment('snapshot HPP saat invoice dibuat (qty × purchase_price)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoice_items', 'cogs_amount')) {
                $table->dropColumn('cogs_amount');
            }
            if (Schema::hasColumn('sales_invoice_items', 'reversed_qty')) {
                $table->dropColumn('reversed_qty');
            }
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoice_items', 'reversed_qty')) {
                $table->dropColumn('reversed_qty');
            }
        });
    }
};
