<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('purchase_payments', 'payment_number')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->string('payment_number', 50)->nullable()->unique()->after('id');
            });

            // Backfill existing purchase payments
            $purchasePayments = DB::table('purchase_payments')->orderBy('id')->get();
            $seqMap = [];
            foreach ($purchasePayments as $p) {
                $prefix = ($p->method === 'cash') ? 'BKK' : 'BBK';
                $dateStr = $p->payment_date ? Carbon::parse($p->payment_date)->format('d/m/Y') : Carbon::parse($p->created_at)->format('d/m/Y');
                $key = $prefix . '-' . $dateStr;
                $seqMap[$key] = ($seqMap[$key] ?? 0) + 1;
                $number = $key . '-' . str_pad($seqMap[$key], 4, '0', STR_PAD_LEFT);

                DB::table('purchase_payments')->where('id', $p->id)->update(['payment_number' => $number]);
            }
        }

        if (!Schema::hasColumn('sales_payments', 'payment_number')) {
            Schema::table('sales_payments', function (Blueprint $table) {
                $table->string('payment_number', 50)->nullable()->unique()->after('id');
            });

            // Backfill existing sales payments
            $salesPayments = DB::table('sales_payments')->orderBy('id')->get();
            $seqMap = [];
            foreach ($salesPayments as $sp) {
                $prefix = ($sp->method === 'cash') ? 'BKM' : 'BBM';
                $dateStr = $sp->payment_date ? Carbon::parse($sp->payment_date)->format('d/m/Y') : Carbon::parse($sp->created_at)->format('d/m/Y');
                $key = $prefix . '-' . $dateStr;
                $seqMap[$key] = ($seqMap[$key] ?? 0) + 1;
                $number = $key . '-' . str_pad($seqMap[$key], 4, '0', STR_PAD_LEFT);

                DB::table('sales_payments')->where('id', $sp->id)->update(['payment_number' => $number]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('purchase_payments', 'payment_number')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->dropColumn('payment_number');
            });
        }

        if (Schema::hasColumn('sales_payments', 'payment_number')) {
            Schema::table('sales_payments', function (Blueprint $table) {
                $table->dropColumn('payment_number');
            });
        }
    }
};
