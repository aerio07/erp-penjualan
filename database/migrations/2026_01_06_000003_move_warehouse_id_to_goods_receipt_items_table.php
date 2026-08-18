<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan warehouse_id pada goods_receipt_items
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipt_items', 'warehouse_id')) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('goods_receipt_id')
                    ->constrained('warehouses')
                    ->restrictOnDelete();
            }
        });

        // 2. Salin warehouse_id dari goods_receipts ke goods_receipt_items jika ada data lama
        $items = DB::table('goods_receipt_items')
            ->join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
            ->select('goods_receipt_items.id as item_id', 'goods_receipts.warehouse_id as header_warehouse_id')
            ->whereNull('goods_receipt_items.warehouse_id')
            ->get();

        foreach ($items as $item) {
            if ($item->header_warehouse_id) {
                DB::table('goods_receipt_items')
                    ->where('id', $item->item_id)
                    ->update(['warehouse_id' => $item->header_warehouse_id]);
            }
        }

        // 3. Ubah goods_receipts.warehouse_id menjadi nullable
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipts', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipt_items', 'warehouse_id')) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn('warehouse_id');
            }
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipts', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable(false)->change();
            }
        });
    }
};
