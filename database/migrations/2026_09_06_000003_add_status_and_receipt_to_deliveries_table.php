<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->enum('status', ['in_transit', 'delivered', 'cancelled'])
                ->default('in_transit')
                ->after('condition_status');
            $table->date('received_date')->nullable()->after('delivery_date');
            $table->text('receipt_notes')->nullable()->after('recipient_name');
        });

        // Set seluruh Surat Jalan lama yang sudah ada di database menjadi 'delivered'
        DB::table('deliveries')->update([
            'status' => 'delivered',
            'received_date' => DB::raw('delivery_date'),
        ]);
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['status', 'received_date', 'receipt_notes']);
        });
    }
};
