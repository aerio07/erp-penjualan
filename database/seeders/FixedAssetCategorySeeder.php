<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\FixedAssetCategory;
use Illuminate\Database\Seeder;

class FixedAssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'TNH',
                'name' => 'Tanah',
                'account_code' => '1-2010',
                'description' => 'Aset tetap berupa tanah dan lahan',
                'useful_life_months' => 0, // Tanah tidak disusutkan
            ],
            [
                'code' => 'BGN',
                'name' => 'Bangunan',
                'account_code' => '1-2020',
                'description' => 'Gedung, kantor, dan bangunan permanen',
                'useful_life_months' => 240, // 20 tahun
            ],
            [
                'code' => 'KND',
                'name' => 'Kendaraan',
                'account_code' => '1-2030',
                'description' => 'Mobil operasional, motor, dan armada pengiriman',
                'useful_life_months' => 60, // 5 tahun
            ],
            [
                'code' => 'PRL',
                'name' => 'Peralatan Kantor',
                'account_code' => '1-2100',
                'description' => 'Komputer, printer, mebel, AC, dan peralatan kantor',
                'useful_life_months' => 48, // 4 tahun
            ],
        ];

        foreach ($categories as $cat) {
            $account = ChartOfAccount::where('code', $cat['account_code'])->first();
            if ($account) {
                FixedAssetCategory::updateOrCreate(
                    ['code' => $cat['code']],
                    [
                        'name' => $cat['name'],
                        'chart_of_account_id' => $account->id,
                        'description' => $cat['description'],
                        'useful_life_months' => $cat['useful_life_months'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
