<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('Pembuat PR');
            $table->string('division')->nullable()->comment('Divisi peminta');
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete()->comment('Group Item / Kategori');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->comment('Gudang tujuan penyerahan');
            $table->date('request_date')->comment('Tanggal pengajuan PR');
            $table->date('required_date')->comment('Date Needed / Tanggal dibutuhkan');
            $table->date('expired_date')->nullable()->comment('Batas waktu berlakunya PR');
            $table->enum('status', ['draft', 'revised', 'waiting_approval', 'confirmed', 'done', 'approved', 'rejected', 'ordered', 'cancelled'])->default('draft');
            $table->text('notes')->nullable()->comment('Catatan/keperluan pengadaan');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('qty')->comment('Jumlah yang diajukan');
            $table->integer('qty_ordered')->default(0)->comment('Jumlah yang sudah diproses ke PO');
            $table->text('notes')->nullable()->comment('Catatan spesifik barang');
            $table->timestamps();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('purchase_request_id')->nullable()->after('supplier_id')->constrained('purchase_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['purchase_request_id']);
            $table->dropColumn('purchase_request_id');
        });

        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
    }
};