<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_bank_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('PT Bhrama Benua Sejahtera');
            $table->string('receipt_number')->unique();
            $table->string('voucher_number')->nullable();
            $table->string('type')->default('KAS');
            $table->date('transaction_date');
            $table->string('group')->nullable();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('document_file')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_bank_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_bank_receipt_id')->constrained('cash_bank_receipts')->onDelete('cascade');
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_bank_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('PT Bhrama Benua Sejahtera');
            $table->string('expense_number')->unique();
            $table->string('voucher_number')->nullable();
            $table->string('type')->default('KAS');
            $table->date('transaction_date');
            $table->string('group')->nullable();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('document_file')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_bank_expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_bank_expense_id')->constrained('cash_bank_expenses')->onDelete('cascade');
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_bank_expense_items');
        Schema::dropIfExists('cash_bank_expenses');
        Schema::dropIfExists('cash_bank_receipt_items');
        Schema::dropIfExists('cash_bank_receipts');
    }
};
