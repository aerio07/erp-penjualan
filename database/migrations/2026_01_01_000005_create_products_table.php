<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->comment('pcs, box, kg, dll');
            $table->decimal('purchase_price', 15, 2)->default(0)->comment('harga beli acuan');
            $table->decimal('sell_price', 15, 2)->default(0)->comment('harga jual acuan');
            $table->integer('min_stock')->default(0)->comment('ambang batas restock');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
