<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipt_items', 'shortage_reason')) {
                $table->enum('shortage_reason', ['none', 'not_shipped', 'damaged_in_transit'])
                    ->nullable()
                    ->default('none')
                    ->after('condition');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipt_items', 'shortage_reason')) {
                $table->dropColumn('shortage_reason');
            }
        });
    }
};
