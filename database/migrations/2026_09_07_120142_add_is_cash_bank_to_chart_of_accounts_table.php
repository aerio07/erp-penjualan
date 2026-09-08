<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->boolean('is_cash_bank')->default(false)->after('is_active')
                  ->comment('Menandai akun ini sebagai akun Kas/Bank untuk keperluan laporan arus kas');
        });

        // Auto-set existing cash & bank accounts (konvensi kode 1-11xx)
        DB::table('chart_of_accounts')
            ->where('code', 'LIKE', '1-11%')
            ->where('type', 'asset')
            ->update(['is_cash_bank' => true]);
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('is_cash_bank');
        });
    }
};
