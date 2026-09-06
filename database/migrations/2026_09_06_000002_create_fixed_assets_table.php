<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('fixed_asset_categories')->restrictOnDelete();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->enum('acquisition_type', ['purchase', 'opening_balance'])->default('purchase');
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->unsignedInteger('useful_life_months')->default(0);
            $table->string('depreciation_method')->default('straight_line');
            $table->enum('status', ['active', 'maintenance', 'disposed', 'sold'])->default('active');
            $table->string('location')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('batch_reference')->nullable()->index();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
