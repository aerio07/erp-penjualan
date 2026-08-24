<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('name')->constrained('product_categories')->nullOnDelete();
        });

        // Migrasi data kategori yang sudah ada di tabel products ke product_categories
        $existingCategories = DB::table('products')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        $counter = 1;
        foreach ($existingCategories as $catName) {
            $code = 'KAT-' . str_pad($counter++, 3, '0', STR_PAD_LEFT);
            $catId = DB::table('product_categories')->insertGetId([
                'code' => $code,
                'name' => $catName,
                'description' => 'Kategori ' . $catName,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('products')->where('category', $catName)->update(['category_id' => $catId]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('product_categories');
    }
};
