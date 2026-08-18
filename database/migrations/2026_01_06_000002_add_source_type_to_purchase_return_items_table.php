<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_return_items', 'source_type')) {
                $table->enum('source_type', ['accepted', 'rejected'])
                    ->default('accepted')
                    ->after('goods_receipt_item_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_return_items', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};
