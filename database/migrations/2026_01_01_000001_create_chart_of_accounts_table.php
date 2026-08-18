<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('mis. 1-1100');
            $table->string('name')->comment('mis. Kas');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
