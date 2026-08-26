<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('npwp');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            $table->string('bank_account_holder', 255)->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_number', 'bank_account_holder']);
        });
    }
};
