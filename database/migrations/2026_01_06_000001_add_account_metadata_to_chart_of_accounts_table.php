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
            if (!Schema::hasColumn('chart_of_accounts', 'normal_balance')) {
                $table->enum('normal_balance', ['debit', 'credit'])->default('debit')->after('type');
            }

            if (!Schema::hasColumn('chart_of_accounts', 'description')) {
                $table->text('description')->nullable()->after('normal_balance');
            }
        });

        DB::table('chart_of_accounts')
            ->whereIn('type', ['liability', 'equity', 'revenue'])
            ->update(['normal_balance' => 'credit']);
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('chart_of_accounts', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('chart_of_accounts', 'normal_balance')) {
                $table->dropColumn('normal_balance');
            }
        });
    }
};
