<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah ENUM status sales_invoices untuk menambahkan opsi 'draft' dengan default 'draft'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN status ENUM('draft', 'unpaid', 'partial', 'paid') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN status ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid'");
        }
    }
};
