<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('notes')->comment('Nama Sopir');
            $table->string('vehicle_number')->nullable()->after('driver_name')->comment('Nomor Kendaraan / Plat');
            $table->string('expedition')->nullable()->after('vehicle_number')->comment('Ekspedisi / Kurir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['driver_name', 'vehicle_number', 'expedition']);
        });
    }
};
