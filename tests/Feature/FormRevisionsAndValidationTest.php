<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormRevisionsAndValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_fetch_cascading_regions_api()
    {
        $response = $this->actingAs($this->user)->getJson(route('regions.provinces'));
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'DKI Jakarta', 'id' => 'DKI_JAKARTA']);

        $response = $this->actingAs($this->user)->getJson(route('regions.regencies', ['provinceId' => 'DKI_JAKARTA']));
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Kota Jakarta Pusat', 'id' => 'JAKARTA_PUSAT']);

        $response = $this->actingAs($this->user)->getJson(route('regions.districts', ['regencyId' => 'JAKARTA_PUSAT']));
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Gambir', 'id' => 'GAMBIR']);

        $response = $this->actingAs($this->user)->getJson(route('regions.villages', ['districtId' => 'GAMBIR']));
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Gambir', 'postal_code' => '10110']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_auto_generates_customer_code_and_validates_individual_nik()
    {
        // 1. Validation fails if individual (non_pkp) customer has missing NIK
        $response = $this->actingAs($this->user)->post(route('master.customers.store'), [
            'tax_type'     => 'non_pkp',
            'name'         => 'Budi Santoso',
            'phone'        => '081234567890',
            'payment_term' => 'net_30',
            'credit_limit' => 10000000,
            'address'      => 'Jl. Thamrin No. 1, Gambir, Jakarta',
            'nik'          => '', // empty nik
        ]);
        $response->assertSessionHasErrors(['nik']);

        // 2. Validation passes with NIK and auto-generates code
        $response = $this->actingAs($this->user)->post(route('master.customers.store'), [
            'tax_type'     => 'non_pkp',
            'name'         => 'Budi Santoso',
            'nik'          => '3171012345678901',
            'phone'        => '081234567890',
            'email'        => 'budi@example.com',
            'payment_term' => 'net_30',
            'credit_limit' => 10000000,
            'address'      => 'Jl. Thamrin No. 1, Gambir, Jakarta',
            'is_active'    => 1,
        ]);

        $response->assertRedirect(route('master.customers.index'));
        $customer = Customer::where('name', 'Budi Santoso')->first();
        $this->assertNotNull($customer);
        $this->assertStringStartsWith('CUST-', $customer->code);
        $this->assertEquals('3171012345678901', $customer->nik);

        // 3. Edit customer preserves code
        $originalCode = $customer->code;
        $updateResponse = $this->actingAs($this->user)->put(route('master.customers.update', $customer), [
            'tax_type'     => 'non_pkp',
            'name'         => 'Budi Santoso Updated',
            'nik'          => '3171012345678901',
            'phone'        => '081234567890',
            'payment_term' => 'net_30',
            'credit_limit' => 10000000,
            'address'      => 'Jl. Sudirman No. 2, Jakarta',
        ]);
        $updateResponse->assertRedirect(route('master.customers.index'));
        $customer->refresh();
        $this->assertEquals($originalCode, $customer->code);
        $this->assertEquals('Budi Santoso Updated', $customer->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_corporate_customer_npwp_and_uploads_tax_document()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('npwp_dokumen.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('master.customers.store'), [
            'tax_type'     => 'pkp',
            'name'         => 'PT Sumber Makmur Sejahtera',
            'npwp'         => '01.234.567.8-901.000',
            'phone'        => '021-5556677',
            'payment_term' => 'net_60',
            'credit_limit' => 50000000,
            'tax_document' => $file,
            'address'      => 'Kawasan Industri Pulogadung',
        ]);

        $response->assertRedirect(route('master.customers.index'));
        $customer = Customer::where('name', 'PT Sumber Makmur Sejahtera')->first();
        $this->assertNotNull($customer);
        $this->assertNotNull($customer->tax_document);
        Storage::disk('public')->assertExists($customer->tax_document);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_auto_generates_supplier_code_and_handles_document_upload()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('supplier_tax.png');

        // 1. Validation fails if perorangan (non_pkp) has missing/invalid ktp_number
        $failResponse = $this->actingAs($this->user)->post(route('master.suppliers.store'), [
            'name'                => 'Pak Budi Supplier Kayu',
            'supplier_type'       => 'non_pkp',
            'ktp_number'          => '12345', // invalid length
            'phone'               => '08123456789',
            'payment_term'        => 'COD',
            'bank_name'           => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Pak Budi',
            'address'             => 'Sentra Kayu Jepara',
        ]);
        $failResponse->assertSessionHasErrors(['ktp_number']);

        // 2. Validation passes with 16 digit NIK for non_pkp
        $response = $this->actingAs($this->user)->post(route('master.suppliers.store'), [
            'name'                => 'PT Supplier Utama Mandiri',
            'supplier_type'       => 'pkp',
            'npwp'                => '09.876.543.2-109.000',
            'tax_document'        => $file,
            'phone'               => '021-99887766',
            'payment_term'        => 'NET 30',
            'bank_name'           => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'PT Supplier Utama Mandiri',
            'address'             => 'Gedung Wisma Niaga Lt. 5, Gambir, Kota Jakarta Pusat, DKI Jakarta, 10110',
        ]);

        $response->assertRedirect(route('master.suppliers.index'));
        $supplier = Supplier::where('name', 'PT Supplier Utama Mandiri')->first();
        $this->assertNotNull($supplier);
        $this->assertStringStartsWith('SUP-', $supplier->code);
        $this->assertNotNull($supplier->tax_document);
        Storage::disk('public')->assertExists($supplier->tax_document);

        // Preserves code on edit
        $originalCode = $supplier->code;
        $this->actingAs($this->user)->put(route('master.suppliers.update', $supplier), [
            'name'                => 'PT Supplier Utama Mandiri Tbk',
            'supplier_type'       => 'pkp',
            'npwp'                => '09.876.543.2-109.000',
            'phone'               => '021-99887766',
            'payment_term'        => 'NET 30',
            'bank_name'           => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'PT Supplier Utama Mandiri',
            'address'             => 'Gedung Wisma Niaga Lt. 6',
        ]);
        $supplier->refresh();
        $this->assertEquals($originalCode, $supplier->code);
        $this->assertEquals('PT Supplier Utama Mandiri Tbk', $supplier->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_auto_generates_product_code_and_preserves_on_edit()
    {
        $response = $this->actingAs($this->user)->post(route('master.products.store'), [
            'name'           => 'Mechanical Keyboard Pro RGB',
            'unit'           => 'pcs',
            'purchase_price' => 250000,
            'sell_price'     => 350000,
        ]);

        $response->assertRedirect(route('master.products.index'));
        $product = Product::where('name', 'Mechanical Keyboard Pro RGB')->first();
        $this->assertNotNull($product);
        $this->assertStringStartsWith('PRD-', $product->sku);

        // Edit preserves SKU
        $originalSku = $product->sku;
        $this->actingAs($this->user)->put(route('master.products.update', $product), [
            'name'           => 'Mechanical Keyboard Pro RGB Wireless',
            'unit'           => 'unit',
            'purchase_price' => 270000,
            'sell_price'     => 390000,
        ]);
        $product->refresh();
        $this->assertEquals($originalSku, $product->sku);
        $this->assertEquals('Mechanical Keyboard Pro RGB Wireless', $product->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_auto_generates_warehouse_code_and_preserves_on_edit()
    {
        $response = $this->actingAs($this->user)->post(route('master.warehouses.store'), [
            'name'     => 'Gudang Pusat Logistik Cakung',
            'address'  => 'Jl. Raya Cakung No. 88, Jakarta Timur',
            'capacity' => 10000,
        ]);

        $response->assertRedirect(route('master.warehouses.index'));
        $warehouse = Warehouse::where('name', 'Gudang Pusat Logistik Cakung')->first();
        $this->assertNotNull($warehouse);
        $this->assertStringStartsWith('WH-', $warehouse->code);

        // Edit preserves code
        $originalCode = $warehouse->code;
        $this->actingAs($this->user)->put(route('master.warehouses.update', $warehouse), [
            'name'     => 'Gudang Pusat Logistik Cakung Utama',
            'address'  => 'Jl. Raya Cakung No. 88A, Jakarta Timur',
            'capacity' => 12000,
        ]);
        $warehouse->refresh();
        $this->assertEquals($originalCode, $warehouse->code);
        $this->assertEquals('Gudang Pusat Logistik Cakung Utama', $warehouse->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_auto_generates_product_category_code_and_preserves_on_edit()
    {
        $response = $this->actingAs($this->user)->post(route('master.categories.store'), [
            'name'        => 'Peralatan Audio & Visual',
            'description' => 'Kategori untuk speaker, mikrofon, dan display monitor',
        ]);

        $response->assertRedirect(route('master.categories.index'));
        $category = ProductCategory::where('name', 'Peralatan Audio & Visual')->first();
        $this->assertNotNull($category);
        $this->assertStringStartsWith('CAT-', $category->code);

        // Edit preserves code
        $originalCode = $category->code;
        $this->actingAs($this->user)->put(route('master.categories.update', $category), [
            'name'        => 'Peralatan Audio & Video Pro',
            'description' => 'Kategori produk audio dan studio',
        ]);
        $category->refresh();
        $this->assertEquals($originalCode, $category->code);
        $this->assertEquals('Peralatan Audio & Video Pro', $category->name);
    }
}
