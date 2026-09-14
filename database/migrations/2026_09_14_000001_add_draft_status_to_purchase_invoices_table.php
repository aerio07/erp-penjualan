<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah ENUM status purchase_invoices untuk menambahkan opsi 'draft' dengan default 'draft'
        DB::statement("ALTER TABLE purchase_invoices MODIFY COLUMN status ENUM('draft', 'unpaid', 'partial', 'paid') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE purchase_invoices MODIFY COLUMN status ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid'");
    }
};
