<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('in', 'out', 'adjustment', 'return_in', 'return_out', 'transfer_in', 'transfer_out', 'return_in_damaged') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('in', 'out', 'adjustment', 'return_in', 'return_out', 'transfer_in', 'transfer_out') NOT NULL");
    }
};
