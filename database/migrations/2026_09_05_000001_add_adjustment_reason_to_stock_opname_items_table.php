<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_opname_items', 'adjustment_reason')) {
                $table->enum('adjustment_reason', [
                    'lost',
                    'damaged_unrecorded',
                    'counting_error',
                    'found_extra',
                    'other'
                ])->nullable()->after('physical_qty');
            }

            if (!Schema::hasColumn('stock_opname_items', 'adjustment_note')) {
                $table->text('adjustment_note')->nullable()->after('adjustment_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_opname_items', 'adjustment_note')) {
                $table->dropColumn('adjustment_note');
            }
            if (Schema::hasColumn('stock_opname_items', 'adjustment_reason')) {
                $table->dropColumn('adjustment_reason');
            }
        });
    }
};
