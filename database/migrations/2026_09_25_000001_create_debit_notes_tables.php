<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('draft_number')->nullable();
            $table->string('dn_number')->unique();
            $table->string('dn_type')->default('AP'); // AP or AR
            $table->string('business_unit')->default('BECCA');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->date('dn_date');
            $table->string('tax_type')->default('NON TAX');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->default('in_process'); // in_process, in_approval, posted, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('debit_note_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_note_id')->constrained('debit_notes')->onDelete('cascade');
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->onDelete('set null');
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->onDelete('set null');
            $table->string('invoice_number');
            $table->string('delivery_order_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('dn_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('debit_note_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_note_id')->constrained('debit_notes')->onDelete('cascade');
            $table->string('invoice_number')->nullable();
            $table->foreignId('debit_chart_of_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('credit_chart_of_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->decimal('outstanding_amount', 15, 2)->default(0);
            $table->decimal('dn_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_note_accounts');
        Schema::dropIfExists('debit_note_invoices');
        Schema::dropIfExists('debit_notes');
    }
};
