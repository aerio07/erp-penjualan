<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->integer('invoiced_qty')->default(0)->after('qty_rejected')
                ->comment('jumlah qty_received yang sudah pernah masuk invoice');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn('invoiced_qty');
        });
    }
};
