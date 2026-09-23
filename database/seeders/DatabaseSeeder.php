<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Urutan penting: master data dulu, baru transaksi.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,           // 1. Users
            ChartOfAccountSeeder::class, // 2. COA
        ]);
    }
}
