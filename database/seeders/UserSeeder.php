<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin Sistem',    'email' => 'admin@erp.test',      'role' => 'admin'],
            ['name' => 'Staff Purchasing','email' => 'purchasing@erp.test',  'role' => 'purchasing'],
            ['name' => 'Staff Gudang',    'email' => 'gudang@erp.test',      'role' => 'gudang'],
            ['name' => 'Staff Sales',     'email' => 'sales@erp.test',       'role' => 'sales'],
            ['name' => 'Staff Finance',   'email' => 'finance@erp.test',     'role' => 'finance'],
        ];

        foreach ($users as $u) {
            User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'     => $u['name'],
                    'password' => Hash::make('password'),
                    'role'     => $u['role'],
                ]
            );
        }
    }
}
