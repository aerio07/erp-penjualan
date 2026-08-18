<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_transfers', function (Blueprint $table) {
            $table->foreignId('shipped_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->after('shipped_by')->constrained('users')->nullOnDelete();
            $table->timestamp('shipped_at')->nullable()->after('transfer_date');
            $table->timestamp('received_at')->nullable()->after('shipped_at');
        });

        DB::statement("ALTER TABLE warehouse_transfers MODIFY COLUMN status ENUM('draft', 'in_transit', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");

        Schema::table('warehouse_transfer_items', function (Blueprint $table) {
            $table->renameColumn('qty_requested', 'qty');
            $table->dropColumn(['qty_received', 'condition']);
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_transfer_items', function (Blueprint $table) {
            $table->renameColumn('qty', 'qty_requested');
            $table->integer('qty_received')->default(0);
            $table->enum('condition', ['baik', 'rusak'])->default('baik');
        });

        DB::statement("ALTER TABLE warehouse_transfers MODIFY COLUMN status ENUM('draft', 'in_transit', 'received', 'cancelled') NOT NULL DEFAULT 'draft'");

        Schema::table('warehouse_transfers', function (Blueprint $table) {
            $table->dropForeign(['shipped_by']);
            $table->dropForeign(['received_by']);
            $table->dropColumn(['shipped_by', 'received_by', 'shipped_at', 'received_at']);
        });
    }
};
