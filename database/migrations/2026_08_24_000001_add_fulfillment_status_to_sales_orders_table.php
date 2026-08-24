<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->enum('fulfillment_status', [
                'pending',
                'backorder',
                'partially_available',
                'ready_to_ship',
                'partially_delivered',
                'delivered',
            ])->default('pending')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('fulfillment_status');
        });
    }
};
