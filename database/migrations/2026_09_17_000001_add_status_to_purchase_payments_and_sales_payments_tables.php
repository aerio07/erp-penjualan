<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('purchase_payments', 'status')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->enum('status', ['draft', 'posted'])->default('draft')->after('amount');
            });

            // Set existing payments to posted
            DB::table('purchase_payments')->update(['status' => 'posted']);
        }

        if (!Schema::hasColumn('sales_payments', 'status')) {
            Schema::table('sales_payments', function (Blueprint $table) {
                $table->enum('status', ['draft', 'posted'])->default('draft')->after('amount');
            });

            // Set existing payments to posted
            DB::table('sales_payments')->update(['status' => 'posted']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('purchase_payments', 'status')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('sales_payments', 'status')) {
            Schema::table('sales_payments', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
