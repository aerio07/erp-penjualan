<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipts', 'auto_generate_invoice')) {
                $table->boolean('auto_generate_invoice')->default(false)->after('is_invoiced');
            }
            if (!Schema::hasColumn('goods_receipts', 'supplier_invoice_number')) {
                $table->string('supplier_invoice_number')->nullable()->after('auto_generate_invoice');
            }
            if (!Schema::hasColumn('goods_receipts', 'invoice_date')) {
                $table->date('invoice_date')->nullable()->after('supplier_invoice_number');
            }
            if (!Schema::hasColumn('goods_receipts', 'due_date')) {
                $table->date('due_date')->nullable()->after('invoice_date');
            }
            if (!Schema::hasColumn('goods_receipts', 'invoice_tax_rate')) {
                $table->decimal('invoice_tax_rate', 5, 2)->nullable()->after('due_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $columns = ['auto_generate_invoice', 'supplier_invoice_number', 'invoice_date', 'due_date', 'invoice_tax_rate'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('goods_receipts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
