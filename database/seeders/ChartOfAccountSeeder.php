<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // === ASET (1-xxxx) ===
            // Aset Lancar
            ['code' => '1-1100', 'name' => 'Kas & Bank',             'type' => 'asset',     'parent_code' => null],
            ['code' => '1-1110', 'name' => 'Bank BCA',               'type' => 'asset',     'parent_code' => '1-1100'],
            ['code' => '1-1120', 'name' => 'Bank Mandiri',           'type' => 'asset',     'parent_code' => '1-1100'],
            ['code' => '1-1200', 'name' => 'Piutang Usaha',          'type' => 'asset',     'parent_code' => null],
            ['code' => '1-1210', 'name' => 'Piutang Lain-lain',      'type' => 'asset',     'parent_code' => '1-1200'],
            ['code' => '1-1300', 'name' => 'Uang Muka Pembelian',    'type' => 'asset',     'parent_code' => null],
            ['code' => '1-1400', 'name' => 'Persediaan Barang',      'type' => 'asset',     'parent_code' => null],
            ['code' => '1-1500', 'name' => 'PPN Masukan',            'type' => 'asset',     'parent_code' => null],
            ['code' => '1-1900', 'name' => 'Biaya Dibayar Dimuka',   'type' => 'asset',     'parent_code' => null],
            // Aset Tetap
            ['code' => '1-2100', 'name' => 'Peralatan Kantor',       'type' => 'asset',     'parent_code' => null],
            ['code' => '1-2900', 'name' => 'Akumulasi Penyusutan',   'type' => 'asset',     'parent_code' => '1-2100'],

            // === KEWAJIBAN (2-xxxx) ===
            ['code' => '2-1100', 'name' => 'Hutang Usaha',            'type' => 'liability', 'parent_code' => null],
            ['code' => '2-1200', 'name' => 'Hutang Lain-lain',        'type' => 'liability', 'parent_code' => '2-1100'],
            ['code' => '2-1300', 'name' => 'Uang Muka Penjualan',     'type' => 'liability', 'parent_code' => null],
            ['code' => '2-1400', 'name' => 'PPN Keluaran',            'type' => 'liability', 'parent_code' => null],
            ['code' => '2-1500', 'name' => 'PPh Pasal 25 Terutang',   'type' => 'liability', 'parent_code' => null],
            ['code' => '2-2100', 'name' => 'Hutang Jangka Panjang',   'type' => 'liability', 'parent_code' => null],

            // === EKUITAS (3-xxxx) ===
            ['code' => '3-1100', 'name' => 'Modal Pemilik',           'type' => 'equity',    'parent_code' => null],
            ['code' => '3-1200', 'name' => 'Laba Ditahan',            'type' => 'equity',    'parent_code' => null],
            ['code' => '3-1300', 'name' => 'Laba/Rugi Berjalan',      'type' => 'equity',    'parent_code' => null],

            // === PENDAPATAN (4-xxxx) ===
            ['code' => '4-1100', 'name' => 'Penjualan',                    'type' => 'revenue',   'parent_code' => null],
            ['code' => '4-1200', 'name' => 'Retur Penjualan',              'type' => 'revenue',   'parent_code' => '4-1100'],
            ['code' => '4-1300', 'name' => 'Potongan Penjualan',           'type' => 'revenue',   'parent_code' => '4-1100'],
            ['code' => '4-1400', 'name' => 'Pendapatan Penjualan Reject',   'type' => 'revenue',   'parent_code' => '4-1100'],
            ['code' => '4-9100', 'name' => 'Pendapatan Lain-lain',         'type' => 'revenue',   'parent_code' => null],

            // === BEBAN (5-xxxx) ===
            ['code' => '5-1100', 'name' => 'Harga Pokok Penjualan (HPP)',  'type' => 'expense',   'parent_code' => null],
            ['code' => '5-1200', 'name' => 'Retur Pembelian',               'type' => 'expense',   'parent_code' => '5-1100'],
            ['code' => '5-1300', 'name' => 'Kerugian Persediaan Rusak',     'type' => 'expense',   'parent_code' => '5-1100'],
            ['code' => '5-1400', 'name' => 'HPP Penjualan Reject',          'type' => 'expense',   'parent_code' => '5-1100'],
            ['code' => '5-2100', 'name' => 'Beban Gaji',                    'type' => 'expense',   'parent_code' => null],
            ['code' => '5-2200', 'name' => 'Beban Sewa',                    'type' => 'expense',   'parent_code' => null],
            ['code' => '5-2300', 'name' => 'Beban Listrik & Air',           'type' => 'expense',   'parent_code' => null],
            ['code' => '5-2400', 'name' => 'Beban Transportasi',            'type' => 'expense',   'parent_code' => null],
            ['code' => '5-2500', 'name' => 'Beban Penyusutan',              'type' => 'expense',   'parent_code' => null],
            ['code' => '5-9900', 'name' => 'Beban Lain-lain',               'type' => 'expense',   'parent_code' => null],
        ];

        // Step 1: Create or update all accounts
        foreach ($accounts as $account) {
            $normalBalance = in_array($account['type'], ['asset', 'expense']) ? 'debit' : 'credit';

            ChartOfAccount::updateOrCreate(
                ['code' => $account['code']],
                [
                    'name'           => $account['name'],
                    'type'           => $account['type'],
                    'normal_balance' => $normalBalance,
                    'is_active'      => true,
                ]
            );
        }

        // Step 2: Link parent IDs
        foreach ($accounts as $account) {
            if (!empty($account['parent_code'])) {
                $parent = ChartOfAccount::where('code', $account['parent_code'])->first();
                if ($parent) {
                    ChartOfAccount::where('code', $account['code'])->update(['parent_id' => $parent->id]);
                }
            }
        }
    }
}
