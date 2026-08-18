<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- PURCHASE ORDERS ---
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('pembuat PO');
            $table->enum('status', ['draft', 'waiting_approval', 'confirmed', 'partially_received', 'done', 'cancelled'])
                ->default('draft');
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0)->comment('diskon global header');
            $table->decimal('tax_rate', 5, 2)->default(11)->comment('PPN dalam persen, dinamis');
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0)->comment('setelah diskon + pajak');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // --- PURCHASE ORDER ITEMS ---
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('qty_ordered');
            $table->decimal('unit_price', 15, 2)->comment('snapshot harga saat PO dibuat');
            $table->decimal('discount_percent', 5, 2)->default(0)->comment('diskon per item %');
            $table->decimal('discount_amount', 15, 2)->default(0)->comment('diskon per item Rp');
            $table->decimal('subtotal', 15, 2)->comment('qty × unit_price - discount');
            $table->timestamps();
        });

        // --- GOODS RECEIPTS ---
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('penerima barang');
            $table->enum('qc_status', ['pending', 'passed', 'failed', 'partial'])->default('pending');
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // --- GOODS RECEIPT ITEMS ---
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->restrictOnDelete()
                ->comment('untuk tracking partial receiving');
            $table->integer('qty_received')->comment('jumlah lolos QC');
            $table->integer('qty_rejected')->default(0)->comment('jumlah rusak/reject');
            $table->decimal('unit_cost', 15, 2)->comment('untuk perhitungan HPP');
            $table->string('condition', 50)->default('Good');
            $table->timestamps();
        });

        // --- PURCHASE INVOICES ---
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->string('supplier_invoice_number')->nullable()->comment('nomor invoice dari supplier');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(11);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // --- PURCHASE RETURNS ---
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('goods_receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->enum('status', ['draft', 'sent', 'completed'])->default('draft');
            $table->date('return_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // --- PURCHASE RETURN ITEMS ---
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('goods_receipt_item_id')->constrained()->restrictOnDelete();
            $table->integer('qty')->comment('jumlah yang dikembalikan ke supplier');
            $table->decimal('unit_cost', 15, 2);
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // --- PURCHASE PAYMENTS ---
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('finance yang input');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->enum('method', ['transfer', 'cash', 'giro', 'cek'])->default('transfer');
            $table->string('reference_number')->nullable()->comment('nomor transfer/cek/giro');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
