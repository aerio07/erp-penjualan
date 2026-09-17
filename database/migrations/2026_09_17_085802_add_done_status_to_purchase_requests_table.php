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
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('draft', 'waiting_approval', 'confirmed', 'done', 'approved', 'ordered', 'cancelled') NOT NULL DEFAULT 'draft'");

        // PR yang berstatus approved menjadi confirmed
        \Illuminate\Support\Facades\DB::table('purchase_requests')->where('status', 'approved')->update(['status' => 'confirmed']);

        // PR yang berstatus ordered menjadi done
        \Illuminate\Support\Facades\DB::table('purchase_requests')->where('status', 'ordered')->update(['status' => 'done']);

        // PR yang seluruh itemnya sudah terpenuhi ke PO menjadi done
        $fullPrIds = \Illuminate\Support\Facades\DB::table('purchase_request_items')
            ->select('purchase_request_id')
            ->groupBy('purchase_request_id')
            ->havingRaw('MIN(qty_ordered >= qty) = 1 AND SUM(qty_ordered) > 0')
            ->pluck('purchase_request_id');

        if ($fullPrIds->isNotEmpty()) {
            \Illuminate\Support\Facades\DB::table('purchase_requests')
                ->whereIn('id', $fullPrIds)
                ->whereIn('status', ['confirmed', 'approved', 'ordered'])
                ->update(['status' => 'done']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('purchase_requests')->where('status', 'done')->update(['status' => 'confirmed']);
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('draft', 'waiting_approval', 'approved', 'rejected', 'confirmed', 'ordered', 'cancelled') NOT NULL DEFAULT 'draft'");
    }
};
