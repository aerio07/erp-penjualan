<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->string('approvable_type')->comment('Polymorphic: App\\Models\\PurchaseOrder, dll');
            $table->unsignedBigInteger('approvable_id');
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->decimal('amount', 15, 2)->nullable()->comment('nilai transaksi untuk threshold');
            $table->text('notes')->nullable()->comment('catatan dari requester');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->index(['approvable_type', 'approvable_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
