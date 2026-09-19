<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_payments', 'chart_of_account_id')) {
                $table->foreignId('chart_of_account_id')
                    ->nullable()
                    ->after('sales_invoice_id')
                    ->constrained('chart_of_accounts')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('sales_payments', 'payment_type')) {
                $table->string('payment_type', 20)
                    ->nullable()
                    ->default('bank')
                    ->after('method');
            }
        });

        // Backfill data lama jika ada
        $defaultBankCoa = DB::table('chart_of_accounts')->where('code', '1-1110')->first()
            ?? DB::table('chart_of_accounts')->where('code', '1-1100')->first();
        $defaultKasCoa = DB::table('chart_of_accounts')->where('code', '1-1100')->first()
            ?? $defaultBankCoa;

        if ($defaultBankCoa) {
            DB::table('sales_payments')
                ->whereNull('chart_of_account_id')
                ->where('method', '!=', 'cash')
                ->update([
                    'chart_of_account_id' => $defaultBankCoa->id,
                    'payment_type' => 'bank'
                ]);
        }

        if ($defaultKasCoa) {
            DB::table('sales_payments')
                ->whereNull('chart_of_account_id')
                ->where('method', 'cash')
                ->update([
                    'chart_of_account_id' => $defaultKasCoa->id,
                    'payment_type' => 'kas'
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('sales_payments', function (Blueprint $table) {
            if (Schema::hasColumn('sales_payments', 'chart_of_account_id')) {
                $table->dropForeign(['chart_of_account_id']);
                $table->dropColumn('chart_of_account_id');
            }

            if (Schema::hasColumn('sales_payments', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
        });
    }
};
