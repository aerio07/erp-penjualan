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
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('draft', 'revised', 'waiting_approval', 'confirmed', 'done', 'approved', 'ordered', 'rejected', 'cancelled') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('draft', 'waiting_approval', 'confirmed', 'done', 'approved', 'ordered', 'rejected', 'cancelled') NOT NULL DEFAULT 'draft'");
        }
    }
};
