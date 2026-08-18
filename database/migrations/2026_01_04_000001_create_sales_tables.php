<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- SALES ORDERS ---
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('sales yang input');
            $table->enum('status', ['draft', 'waiting_approval', 'confirmed', 'partially_delivered', 'done', 'cancelled'])
                ->default('draft');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0)->comment('diskon global header');
            $table->decimal('tax_rate', 5, 2)->default(11)->comment('PPN dalam persen, dinamis');
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // --- SALES ORDER ITEMS ---
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('qty_ordered');
            $table->decimal('unit_price', 15, 2)->comment('snapshot harga saat SO dibuat');
            $table->decimal('discount_percent', 5, 2)->default(0)->comment('diskon per item %');
            $table->decimal('discount_amount', 15, 2)->default(0)->comment('diskon per item Rp');
            $table->decimal('subtotal', 15, 2)->comment('qty × unit_price - discount');
            $table->timestamps();
        });

        // --- DELIVERIES (Surat Jalan) ---
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique()->comment('nomor surat jalan');
            $table->foreignId('sales_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('yang input');
            $table->enum('condition_status', ['baik', 'rusak', 'partial'])->default('baik');
            $table->date('delivery_date');
            $table->text('shipping_address')->nullable();
            $table->string('recipient_name')->nullable()->comment('nama penerima di customer');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // --- DELIVERY ITEMS ---
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained()->restrictOnDelete()
                ->comment('untuk tracking partial delivery');
            $table->integer('qty_delivered');
            $table->string('condition', 50)->default('Good');
            $table->timestamps();
        });

        // --- SALES INVOICES ---
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('sales_order_id')->constrained()->restrictOnDelete();
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

        // --- SALES RETURNS ---
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('delivery_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->enum('status', ['draft', 'received', 'completed'])->default('draft');
            $table->date('return_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // --- SALES RETURN ITEMS ---
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('delivery_item_id')->constrained()->restrictOnDelete();
            $table->integer('qty')->comment('jumlah yang dikembalikan customer');
            $table->enum('condition', ['baik', 'rusak'])->default('baik');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // --- SALES PAYMENTS ---
        Schema::create('sales_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('finance yang input');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->enum('method', ['transfer', 'cash', 'giro', 'cek'])->default('transfer');
            $table->string('reference_number')->nullable()->comment('nomor transfer/bukti bayar');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_payments');
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('delivery_items');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
