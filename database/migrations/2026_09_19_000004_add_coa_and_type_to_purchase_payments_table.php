<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_payments', 'chart_of_account_id')) {
                $table->foreignId('chart_of_account_id')
                    ->nullable()
                    ->after('purchase_invoice_id')
                    ->constrained('chart_of_accounts')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('purchase_payments', 'payment_type')) {
                $table->string('payment_type', 20)
                    ->nullable()
                    ->default('bank')
                    ->after('method');
            }
        });

        // Backfill data lama jika ada
        $defaultCoa = DB::table('chart_of_accounts')->where('code', '1-1110')->first()
            ?? DB::table('chart_of_accounts')->where('code', '1-1100')->first();

        if ($defaultCoa) {
            DB::table('purchase_payments')
                ->whereNull('chart_of_account_id')
                ->update([
                    'chart_of_account_id' => $defaultCoa->id,
                    'payment_type' => DB::raw("CASE WHEN method = 'cash' THEN 'kas' ELSE 'bank' END")
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_payments', 'chart_of_account_id')) {
                $table->dropForeign(['chart_of_account_id']);
                $table->dropColumn('chart_of_account_id');
            }

            if (Schema::hasColumn('purchase_payments', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
        });
    }
};
