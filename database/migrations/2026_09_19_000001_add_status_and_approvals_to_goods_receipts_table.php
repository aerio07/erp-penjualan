<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipts', 'status')) {
                $table->enum('status', ['draft', 'waiting_approval', 'confirmed', 'rejected', 'revised'])
                    ->default('draft')
                    ->after('qc_status');
            }
            if (!Schema::hasColumn('goods_receipts', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('notes')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('goods_receipts', 'approved_at')) {
                $table->timestamp('approved_at')
                    ->nullable()
                    ->after('approved_by');
            }
            if (!Schema::hasColumn('goods_receipts', 'rejection_reason')) {
                $table->text('rejection_reason')
                    ->nullable()
                    ->after('approved_at');
            }
        });

        // Set existing records to 'confirmed'
        DB::table('goods_receipts')->update(['status' => 'confirmed']);
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipts', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('goods_receipts', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('goods_receipts', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('goods_receipts', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
