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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_online', 15, 2)->nullable()->after('sell_price');
            $table->decimal('price_offline', 15, 2)->nullable()->after('price_online');
            $table->decimal('length_cm', 8, 2)->nullable()->after('price_offline');
            $table->decimal('width_cm', 8, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 8, 2)->nullable()->after('width_cm');
            $table->decimal('weight_kg', 8, 2)->nullable()->after('height_cm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'price_online',
                'price_offline',
                'length_cm',
                'width_cm',
                'height_cm',
                'weight_kg',
            ]);
        });
    }
};
