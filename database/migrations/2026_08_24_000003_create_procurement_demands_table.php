<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_demands', function (Blueprint $table) {
            $table->id();
            $table->string('demand_number')->unique();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('qty_demanded')->default(0);
            $table->integer('qty_procured')->default(0);
            $table->integer('qty_fulfilled')->default(0);
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'ordered', 'fulfilled', 'cancelled'])->default('pending');
            $table->date('required_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['sales_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_demands');
    }
};
