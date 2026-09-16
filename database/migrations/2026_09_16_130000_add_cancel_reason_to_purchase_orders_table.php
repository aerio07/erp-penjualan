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
        if (Schema::hasTable('purchase_orders') && !Schema::hasColumn('purchase_orders', 'cancel_reason')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->text('cancel_reason')->nullable()->after('notes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'cancel_reason')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('cancel_reason');
            });
        }
    }
};
