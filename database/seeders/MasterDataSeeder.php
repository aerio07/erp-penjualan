<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Warehouses
        $warehouses = [
            ['code' => 'WH-01', 'name' => 'Gudang Utama Jakarta',  'address' => 'Jl. Industri No.1, Jakarta'],
            ['code' => 'WH-02', 'name' => 'Gudang Cabang Surabaya', 'address' => 'Jl. Raya Waru No.5, Surabaya'],
        ];
        foreach ($warehouses as $w) {
            Warehouse::firstOrCreate(['code' => $w['code']], $w);
        }

        // Suppliers
        $suppliers = [
            [
                'code'           => 'SUP-001',
                'name'           => 'PT Sumber Makmur',
                'contact_person' => 'Budi Santoso',
                'phone'          => '021-5551234',
                'email'          => 'budi@sumbermakmur.co.id',
                'address'        => 'Jl. Pasar Baru No.10, Jakarta',
                'payment_term'   => 'NET 30',
            ],
            [
                'code'           => 'SUP-002',
                'name'           => 'CV Jaya Abadi',
                'contact_person' => 'Dewi Lestari',
                'phone'          => '031-7775678',
                'email'          => 'dewi@jayaabadi.co.id',
                'address'        => 'Jl. Kembang Jepun No.22, Surabaya',
                'payment_term'   => 'NET 14',
            ],
        ];
        foreach ($suppliers as $s) {
            Supplier::firstOrCreate(['code' => $s['code']], $s);
        }

        // Customers
        $customers = [
            [
                'code'           => 'CUST-001',
                'name'           => 'PT Maju Bersama',
                'contact_person' => 'Andi Wijaya',
                'phone'          => '021-6661234',
                'email'          => 'andi@majubersama.co.id',
                'address'        => 'Jl. Sudirman No.45, Jakarta',
                'credit_limit'   => 100_000_000,
                'payment_term'   => 'NET 30',
            ],
            [
                'code'           => 'CUST-002',
                'name'           => 'UD Sejahtera Mandiri',
                'contact_person' => 'Rina Puspita',
                'phone'          => '022-3334567',
                'email'          => 'rina@sejahtera.co.id',
                'address'        => 'Jl. Asia Afrika No.12, Bandung',
                'credit_limit'   => 50_000_000,
                'payment_term'   => 'NET 14',
            ],
        ];
        foreach ($customers as $c) {
            Customer::firstOrCreate(['code' => $c['code']], $c);
        }

        // Products
        $products = [
            [
                'sku'            => 'PRD-001',
                'name'           => 'Laptop Acer Aspire 5',
                'category'       => 'Elektronik',
                'unit'           => 'pcs',
                'purchase_price' => 6_500_000,
                'sell_price'     => 8_500_000,
                'min_stock'      => 5,
            ],
            [
                'sku'            => 'PRD-002',
                'name'           => 'Mouse Wireless Logitech M170',
                'category'       => 'Aksesoris',
                'unit'           => 'pcs',
                'purchase_price' => 75_000,
                'sell_price'     => 110_000,
                'min_stock'      => 20,
            ],
            [
                'sku'            => 'PRD-003',
                'name'           => 'Keyboard Mechanical Keychron K2',
                'category'       => 'Aksesoris',
                'unit'           => 'pcs',
                'purchase_price' => 850_000,
                'sell_price'     => 1_200_000,
                'min_stock'      => 10,
            ],
            [
                'sku'            => 'PRD-004',
                'name'           => 'Monitor LG 24" IPS',
                'category'       => 'Elektronik',
                'unit'           => 'pcs',
                'purchase_price' => 2_200_000,
                'sell_price'     => 3_000_000,
                'min_stock'      => 5,
            ],
        ];
        foreach ($products as $p) {
            Product::firstOrCreate(['sku' => $p['sku']], $p);
        }
    }
}
