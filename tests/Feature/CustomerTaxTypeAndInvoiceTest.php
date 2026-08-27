<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerTaxTypeAndInvoiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cannot_create_pkp_customer_without_npwp(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('master.customers.store'), [
            'code'           => 'CUST-PKP-1',
            'name'           => 'PT. Sukses Makmur',
            'phone'          => '08123456789',
            'payment_term'   => 'NET 30',
            'credit_limit'   => 5000000,
            'tax_type'       => 'pkp',
            'npwp'           => '', // Kosong -> harus ditolak
            'address'        => 'Jl. Industri No. 10 Jakarta',
            'is_active'      => 1,
        ]);

        $response->assertSessionHasErrors(['npwp']);
        $this->assertDatabaseMissing('customers', [
            'code' => 'CUST-PKP-1',
        ]);
    }

    public function test_cannot_create_non_pkp_customer_without_nik(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('master.customers.store'), [
            'code'           => 'CUST-NONPKP-NO-NIK',
            'name'           => 'Toko Berkah Mandiri',
            'phone'          => '08198765432',
            'payment_term'   => 'COD',
            'credit_limit'   => 0,
            'tax_type'       => 'non_pkp',
            'npwp'           => null,
            'nik'            => '', // Kosong -> harus ditolak untuk non_pkp
            'address'        => 'Jl. Pasar Baru No. 5',
            'is_active'      => 1,
        ]);

        $response->assertSessionHasErrors(['nik']);
        $this->assertDatabaseMissing('customers', [
            'code' => 'CUST-NONPKP-NO-NIK',
        ]);
    }

    public function test_can_create_non_pkp_customer_without_npwp_when_nik_provided(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('master.customers.store'), [
            'code'           => 'CUST-NONPKP-1',
            'name'           => 'Toko Berkah Mandiri',
            'phone'          => '08198765432',
            'payment_term'   => 'COD',
            'credit_limit'   => 0,
            'tax_type'       => 'non_pkp',
            'npwp'           => null,
            'nik'            => '3201012345670001',
            'address'        => 'Jl. Pasar Baru No. 5',
            'is_active'      => 1,
        ]);

        $response->assertRedirect(route('master.customers.index'));
        $this->assertDatabaseHas('customers', [
            'code'     => 'CUST-NONPKP-1',
            'tax_type' => 'non_pkp',
            'npwp'     => null,
            'nik'      => '3201012345670001',
        ]);

        $cust = Customer::where('code', 'CUST-NONPKP-1')->first();
        $this->assertFalse($cust->isPkp());
    }

    public function test_can_create_pkp_customer_with_npwp(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('master.customers.store'), [
            'code'           => 'CUST-PKP-2',
            'name'           => 'PT. Gemilang Indonesia',
            'phone'          => '08123334445',
            'payment_term'   => 'NET 14',
            'credit_limit'   => 10000000,
            'tax_type'       => 'pkp',
            'npwp'           => '01.234.567.8-901.000',
            'address'        => 'Kawasan Industri Cikarang',
            'is_active'      => 1,
        ]);

        $response->assertRedirect(route('master.customers.index'));
        $this->assertDatabaseHas('customers', [
            'code'     => 'CUST-PKP-2',
            'tax_type' => 'pkp',
            'npwp'     => '01.234.567.8-901.000',
        ]);

        $cust = Customer::where('code', 'CUST-PKP-2')->first();
        $this->assertTrue($cust->isPkp());
    }

    public function test_can_filter_customers_by_tax_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Customer::create([
            'code' => 'CUST-A',
            'name' => 'PT. Alpha PKP',
            'phone' => '081111',
            'payment_term' => 'COD',
            'credit_limit' => 0,
            'tax_type' => 'pkp',
            'npwp' => '01.111.111.1-111.000',
            'address' => 'Alamat A',
            'is_active' => true,
        ]);

        Customer::create([
            'code' => 'CUST-B',
            'name' => 'Toko Beta Non-PKP',
            'phone' => '082222',
            'payment_term' => 'COD',
            'credit_limit' => 0,
            'tax_type' => 'non_pkp',
            'npwp' => null,
            'address' => 'Alamat B',
            'is_active' => true,
        ]);

        // Filter PKP
        $responsePkp = $this->actingAs($admin)->get(route('master.customers.index', ['tax_type' => 'pkp']));
        $responsePkp->assertOk();
        $responsePkp->assertSee('PT. Alpha PKP');
        $responsePkp->assertDontSee('Toko Beta Non-PKP');

        // Filter Non-PKP
        $responseNonPkp = $this->actingAs($admin)->get(route('master.customers.index', ['tax_type' => 'non_pkp']));
        $responseNonPkp->assertOk();
        $responseNonPkp->assertSee('Toko Beta Non-PKP');
        $responseNonPkp->assertDontSee('PT. Alpha PKP');
    }

    public function test_sales_invoice_tax_invoice_number_update_and_views(): void
    {
        $finance = User::factory()->create(['role' => 'finance']);

        $customerPkp = Customer::create([
            'code'         => 'CUST-PKP-TEST',
            'name'         => 'PT. Mega Trading',
            'phone'        => '0812345678',
            'payment_term' => 'NET 30',
            'credit_limit' => 50000000,
            'tax_type'     => 'pkp',
            'npwp'         => '01.999.888.7-654.000',
            'address'      => 'Jl. Gatot Subroto No. 10',
            'is_active'    => true,
        ]);

        $so = SalesOrder::create([
            'so_number'    => 'SO-TEST-001',
            'customer_id'  => $customerPkp->id,
            'user_id'      => $finance->id,
            'order_date'   => now()->toDateString(),
            'status'       => 'confirmed',
            'total_amount' => 111000,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number'     => 'SINV-202608-0001',
            'sales_order_id'     => $so->id,
            'amount'             => 100000,
            'tax_rate'           => 11,
            'tax_amount'         => 11000,
            'total_amount'       => 111000,
            'invoice_date'       => now()->toDateString(),
            'due_date'           => now()->addDays(30)->toDateString(),
            'status'             => 'unpaid',
            'tax_invoice_number' => null,
        ]);

        // 1. Check show page before filling tax_invoice_number
        $responseShow = $this->actingAs($finance)->get(route('sales.invoices.show', $invoice));
        $responseShow->assertOk();
        $responseShow->assertSee('Faktur Pajak (Customer PKP)');
        $responseShow->assertSee('01.999.888.7-654.000');

        // 2. Update Nomor Faktur Pajak
        $responseUpdate = $this->actingAs($finance)->patch(route('sales.invoices.tax-invoice.update', $invoice), [
            'tax_invoice_number' => '010.001-26.00000001',
        ]);
        $responseUpdate->assertRedirect();
        $responseUpdate->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('010.001-26.00000001', $invoice->tax_invoice_number);

        // 3. Verify PDF Export contains tax_invoice_number
        $responsePdf = $this->actingAs($finance)->get(route('pdf.sales-invoice', $invoice));
        $responsePdf->assertOk();
        $responsePdf->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_create_non_pkp_customer_with_nik(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('master.customers.store'), [
            'code'           => 'CUST-NIK-01',
            'name'           => 'Budi Santoso (Perorangan)',
            'phone'          => '081234567890',
            'payment_term'   => 'COD',
            'credit_limit'   => 0,
            'tax_type'       => 'non_pkp',
            'npwp'           => null,
            'nik'            => '3201123456780001',
            'address'        => 'Jl. Mawar No. 12 Bogor',
            'is_active'      => 1,
        ]);

        $response->assertRedirect(route('master.customers.index'));
        $this->assertDatabaseHas('customers', [
            'code'     => 'CUST-NIK-01',
            'tax_type' => 'non_pkp',
            'nik'      => '3201123456780001',
        ]);
    }

    public function test_can_update_customer_nik(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create([
            'code'         => 'CUST-NIK-02',
            'name'         => 'Siti Aminah',
            'phone'        => '08987654321',
            'payment_term' => 'COD',
            'credit_limit' => 0,
            'tax_type'     => 'non_pkp',
            'npwp'         => null,
            'nik'          => null,
            'address'      => 'Jl. Melati No. 4 Bandung',
            'is_active'    => true,
        ]);

        $response = $this->actingAs($admin)->put(route('master.customers.update', $customer), [
            'code'           => 'CUST-NIK-02',
            'name'           => 'Siti Aminah Updated',
            'phone'          => '08987654321',
            'payment_term'   => 'NET 7',
            'credit_limit'   => 1000000,
            'tax_type'       => 'non_pkp',
            'npwp'           => null,
            'nik'            => '3273012345670002',
            'address'        => 'Jl. Melati No. 4 Bandung',
            'is_active'      => 1,
        ]);

        $response->assertRedirect(route('master.customers.index'));
        $this->assertDatabaseHas('customers', [
            'id'   => $customer->id,
            'name' => 'Siti Aminah Updated',
            'nik'  => '3273012345670002',
        ]);
    }
}
