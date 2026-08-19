<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function admin_can_view_dashboard_with_all_metrics()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHasAll([
            'role',
            'totalPiutang',
            'totalHutang',
            'labaBulanIni',
            'saldoKas',
            'trenPenjualan',
            'topProduk',
            'alerts',
            'aktivitas',
        ]);
        $response->assertSee('Beranda / Dashboard Eksekutif');
    }

    #[Test]
    public function finance_user_can_view_dashboard()
    {
        $finance = User::factory()->create(['role' => 'finance']);

        $response = $this->actingAs($finance)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Total Piutang Belum Lunas');
        $response->assertSee('Total Hutang Belum Lunas');
    }

    #[Test]
    public function sales_user_can_view_dashboard()
    {
        $sales = User::factory()->create(['role' => 'sales']);

        $response = $this->actingAs($sales)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Tren Penjualan 30 Hari Terakhir');
    }

    #[Test]
    public function gudang_user_can_view_dashboard()
    {
        $gudang = User::factory()->create(['role' => 'gudang']);

        $response = $this->actingAs($gudang)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Stok Kritis');
        $response->assertSee('Stok Perlu Re-order');
    }
}
