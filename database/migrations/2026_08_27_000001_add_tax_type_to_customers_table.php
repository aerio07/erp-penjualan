<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'tax_type')) {
                $table->enum('tax_type', ['pkp', 'non_pkp'])->default('non_pkp')->after('payment_term');
            }
            if (!Schema::hasColumn('customers', 'npwp')) {
                $table->string('npwp', 50)->nullable()->after('tax_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'tax_type')) {
                $table->dropColumn('tax_type');
            }
        });
    }
};
