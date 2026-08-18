<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_dispositions', function (Blueprint $table) {
            $table->id();
            $table->string('disposition_number')->unique();
            $table->foreignId('sales_return_item_id')->nullable()->constrained('sales_return_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->integer('qty');
            $table->enum('resolution_type', ['write_off', 'sold_as_reject']);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->date('disposed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_dispositions');
    }
};
