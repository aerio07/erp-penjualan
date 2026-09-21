<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bersihkan status "zombie" lama di tabel purchase_requests.
     *
     * Status lama:
     *   - 'approved'  → alias lama untuk 'confirmed' (setelah admin approve)
     *   - 'ordered'   → alias lama untuk 'done' (setelah semua item di-PO-kan)
     *
     * Keduanya sudah dimigrasikan datanya sebelumnya, tapi ENUM MySQL-nya
     * masih menyimpan nilai lama. Migration ini membersihkan sisa data
     * dan memperbarui ENUM ke daftar bersih tanpa alias zombie.
     */
    public function up(): void
    {
        // 1. Konversi sisa data lama (jika masih ada) ke nilai baru yang benar
        DB::table('purchase_requests')
            ->where('status', 'approved')
            ->update(['status' => 'confirmed']);

        DB::table('purchase_requests')
            ->where('status', 'ordered')
            ->update(['status' => 'done']);

        // 2. Perbarui ENUM MySQL ke daftar bersih tanpa zombie (hanya di MySQL)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE purchase_requests
                MODIFY COLUMN status
                ENUM('draft', 'revised', 'waiting_approval', 'confirmed', 'done', 'rejected', 'cancelled')
                NOT NULL DEFAULT 'draft'
            ");
        }
    }

    public function down(): void
    {
        // Kembalikan ENUM ke versi sebelumnya (termasuk alias lama, hanya di MySQL)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE purchase_requests
                MODIFY COLUMN status
                ENUM('draft', 'revised', 'waiting_approval', 'confirmed', 'done', 'approved', 'ordered', 'rejected', 'cancelled')
                NOT NULL DEFAULT 'draft'
            ");
        }
    }
};
