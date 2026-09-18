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
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('draft', 'waiting_approval', 'approved', 'rejected', 'confirmed', 'ordered', 'cancelled') NOT NULL DEFAULT 'draft'");
        }
        \Illuminate\Support\Facades\DB::table('purchase_requests')->where('status', 'ordered')->update(['status' => 'confirmed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('purchase_requests')->where('status', 'confirmed')->update(['status' => 'ordered']);
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('draft', 'waiting_approval', 'approved', 'rejected', 'ordered', 'cancelled') NOT NULL DEFAULT 'draft'");
        }
    }
};
