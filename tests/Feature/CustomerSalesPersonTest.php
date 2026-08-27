<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerSalesPersonTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_create_customer_with_sales_person(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sales = User::factory()->create(['name' => 'Budi Sales', 'role' => 'sales']);

        $response = $this->actingAs($admin)->post(route('master.customers.store'), [
            'code'            => 'CUST-SP-1',
            'name'            => 'PT. Sinergi Sukses',
            'phone'           => '081234567890',
            'payment_term'    => 'NET 30',
            'credit_limit'    => 15000000,
            'sales_person_id' => $sales->id,
            'tax_type'        => 'non_pkp',
            'npwp'            => null,
            'nik'             => '3201123456780001',
            'address'         => 'Kawasan Bisnis Sudirman Jakarta',
            'is_active'       => 1,
        ]);

        $response->assertRedirect(route('master.customers.index'));
        $this->assertDatabaseHas('customers', [
            'code'            => 'CUST-SP-1',
            'sales_person_id' => $sales->id,
        ]);

        $cust = Customer::where('code', 'CUST-SP-1')->first();
        $this->assertNotNull($cust->salesPerson);
        $this->assertEquals($sales->id, $cust->salesPerson->id);
        $this->assertEquals('Budi Sales', $cust->salesPerson->name);
    }

    public function test_can_create_customer_without_sales_person(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('master.customers.store'), [
            'code'            => 'CUST-SP-NULL',
            'name'            => 'Toko Mitra Mandiri',
            'phone'           => '081999888777',
            'payment_term'    => 'COD',
            'credit_limit'    => 0,
            'sales_person_id' => '',
            'tax_type'        => 'non_pkp',
            'npwp'            => null,
            'nik'             => '3201123456780002',
            'address'         => 'Jl. Raya Bogor No. 12',
            'is_active'       => 1,
        ]);

        $response->assertRedirect(route('master.customers.index'));
        $this->assertDatabaseHas('customers', [
            'code'            => 'CUST-SP-NULL',
            'sales_person_id' => null,
        ]);

        $cust = Customer::where('code', 'CUST-SP-NULL')->first();
        $this->assertNull($cust->salesPerson);
    }

    public function test_can_filter_customers_by_sales_person(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $salesA = User::factory()->create(['name' => 'Sales Anton', 'role' => 'sales']);
        $salesB = User::factory()->create(['name' => 'Sales Bambang', 'role' => 'sales']);

        Customer::create([
            'code'            => 'CUST-FLT-A',
            'name'            => 'Customer Pegangan Anton',
            'phone'           => '081111111',
            'payment_term'    => 'COD',
            'credit_limit'    => 0,
            'sales_person_id' => $salesA->id,
            'tax_type'        => 'non_pkp',
            'address'         => 'Alamat Anton',
            'is_active'       => true,
        ]);

        Customer::create([
            'code'            => 'CUST-FLT-B',
            'name'            => 'Customer Pegangan Bambang',
            'phone'           => '082222222',
            'payment_term'    => 'COD',
            'credit_limit'    => 0,
            'sales_person_id' => $salesB->id,
            'tax_type'        => 'non_pkp',
            'address'         => 'Alamat Bambang',
            'is_active'       => true,
        ]);

        // Filter Sales Anton
        $responseA = $this->actingAs($admin)->get(route('master.customers.index', ['sales_person_id' => $salesA->id]));
        $responseA->assertOk();
        $responseA->assertSee('Customer Pegangan Anton');
        $responseA->assertDontSee('Customer Pegangan Bambang');

        // Filter Sales Bambang
        $responseB = $this->actingAs($admin)->get(route('master.customers.index', ['sales_person_id' => $salesB->id]));
        $responseB->assertOk();
        $responseB->assertSee('Customer Pegangan Bambang');
        $responseB->assertDontSee('Customer Pegangan Anton');
    }

    public function test_sales_person_id_set_to_null_on_user_delete(): void
    {
        $sales = User::factory()->create(['name' => 'Sales Resign', 'role' => 'sales']);

        $customer = Customer::create([
            'code'            => 'CUST-RESIGN',
            'name'            => 'PT. Pelanggan Setia',
            'phone'           => '085555555',
            'payment_term'    => 'COD',
            'credit_limit'    => 0,
            'sales_person_id' => $sales->id,
            'tax_type'        => 'non_pkp',
            'address'         => 'Alamat Pelanggan',
            'is_active'       => true,
        ]);

        $this->assertEquals($sales->id, $customer->sales_person_id);

        // Hapus user sales
        $sales->delete();

        $customer->refresh();
        $this->assertNull($customer->sales_person_id);
    }
}
