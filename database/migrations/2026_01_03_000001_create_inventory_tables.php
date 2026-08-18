<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- STOCK MOVEMENTS ---
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment', 'return_in', 'return_out', 'transfer_in', 'transfer_out']);
            $table->integer('quantity');
            $table->decimal('unit_cost', 15, 2)->default(0)->comment('untuk average cost');
            $table->string('reference_type')->nullable()->comment('Polymorphic model');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('movement_date');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('yang input movement');
            $table->timestamps();

            // Composite index wajib untuk kartu stok
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['reference_type', 'reference_id']);
        });

        // --- WAREHOUSE TRANSFERS ---
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('yang membuat transfer');
            $table->enum('status', ['draft', 'in_transit', 'received', 'cancelled'])->default('draft');
            $table->date('transfer_date');
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // --- WAREHOUSE TRANSFER ITEMS ---
        Schema::create('warehouse_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('qty_requested');
            $table->integer('qty_received')->default(0);
            $table->enum('condition', ['baik', 'rusak'])->default('baik');
            $table->timestamps();
        });

        // --- STOCK OPNAMES ---
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number')->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('PIC opname');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->date('opname_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // --- STOCK OPNAME ITEMS ---
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('system_qty')->comment('stok menurut sistem');
            $table->integer('physical_qty')->comment('stok fisik hasil hitung');
            $table->integer('difference')->default(0)->comment('physical - system, bisa negatif');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('warehouse_transfer_items');
        Schema::dropIfExists('warehouse_transfers');
        Schema::dropIfExists('stock_movements');
    }
};
