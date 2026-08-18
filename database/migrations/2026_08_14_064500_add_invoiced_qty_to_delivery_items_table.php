<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_items', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_items', 'invoiced_qty')) {
                $table->integer('invoiced_qty')->default(0)->after('qty_delivered');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_items', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_items', 'invoiced_qty')) {
                $table->dropColumn('invoiced_qty');
            }
        });
    }
};
