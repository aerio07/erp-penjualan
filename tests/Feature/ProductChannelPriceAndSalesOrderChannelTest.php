<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductChannelPriceAndSalesOrderChannelTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->customer = Customer::create([
            'code'      => 'CUST-001',
            'name'      => 'Customer Test Channel',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_can_create_product_with_channel_prices_and_dimensions()
    {
        $category = ProductCategory::create([
            'code'      => 'ELK',
            'name'      => 'Elektronik',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('master.products.store'), [
                'sku'            => 'TEST-CHAN-01',
                'name'           => 'Produk Multi Channel',
                'category_id'    => $category->id,
                'unit'           => 'unit',
                'purchase_price' => 5000000,
                'sell_price'     => 7000000,
                'price_online'   => 7500000,
                'price_offline'  => 6800000,
                'length_cm'      => 35.5,
                'width_cm'       => 25.0,
                'height_cm'      => 4.2,
                'weight_kg'      => 2.15,
                'min_stock'      => 5,
                'is_active'      => '1',
            ]);

        $response->assertRedirect(route('master.products.index'));

        $this->assertDatabaseHas('products', [
            'sku'           => 'TEST-CHAN-01',
            'name'          => 'Produk Multi Channel',
            'sell_price'    => 7000000,
            'price_online'  => 7500000,
            'price_offline' => 6800000,
            'length_cm'     => 35.5,
            'width_cm'      => 25.0,
            'height_cm'     => 4.2,
            'weight_kg'      => 2.15,
        ]);

        $product = Product::where('sku', 'TEST-CHAN-01')->first();
        $this->assertEquals(7500000, $product->getEffectivePrice('online'));
        $this->assertEquals(6800000, $product->getEffectivePrice('offline'));
        $this->assertEquals(7000000, $product->getEffectivePrice('other'));
    }

    #[Test]
    public function it_falls_back_to_sell_price_when_channel_prices_are_null()
    {
        $product = Product::create([
            'sku'            => 'FALLBACK-01',
            'name'           => 'Produk Fallback',
            'unit'           => 'pcs',
            'purchase_price' => 100000,
            'sell_price'     => 150000,
            'price_online'   => null,
            'price_offline'  => null,
            'is_active'      => true,
        ]);

        $this->assertEquals(150000, $product->getEffectivePrice('online'));
        $this->assertEquals(150000, $product->getEffectivePrice('offline'));
        $this->assertEquals(150000, $product->getEffectivePrice(null));
    }

    #[Test]
    public function it_can_create_sales_order_with_specified_channel()
    {
        $product = Product::create([
            'sku'            => 'SO-PRD-01',
            'name'           => 'Produk SO Channel',
            'unit'           => 'pcs',
            'purchase_price' => 100000,
            'sell_price'     => 150000,
            'price_online'   => 160000,
            'price_offline'  => 145000,
            'is_active'      => true,
        ]);

        // 1. Channel Online
        $responseOnline = $this->actingAs($this->adminUser)
            ->post(route('sales.orders.store'), [
                'customer_id'     => $this->customer->id,
                'channel'         => 'online',
                'order_date'      => now()->toDateString(),
                'tax_rate'        => 11,
                'discount_amount' => 0,
                'items'           => [
                    [
                        'product_id'       => $product->id,
                        'qty_ordered'      => 2,
                        'unit_price'       => $product->getEffectivePrice('online'), // 160.000
                        'discount_percent' => 0,
                    ],
                ],
            ]);

        $responseOnline->assertRedirect();
        $this->assertDatabaseHas('sales_orders', [
            'customer_id'  => $this->customer->id,
            'channel'      => 'online',
            'total_amount' => 320000 * 1.11,
        ]);

        // 2. Channel Offline (default saat parameter channel kosong)
        $responseDefault = $this->actingAs($this->adminUser)
            ->post(route('sales.orders.store'), [
                'customer_id'     => $this->customer->id,
                'order_date'      => now()->toDateString(),
                'tax_rate'        => 0,
                'discount_amount' => 0,
                'items'           => [
                    [
                        'product_id'       => $product->id,
                        'qty_ordered'      => 1,
                        'unit_price'       => 145000,
                        'discount_percent' => 0,
                    ],
                ],
            ]);

        $responseDefault->assertRedirect();
        $this->assertDatabaseHas('sales_orders', [
            'customer_id' => $this->customer->id,
            'channel'     => 'offline',
        ]);
    }

    #[Test]
    public function it_renders_product_and_sales_order_pages_cleanly()
    {
        $product = Product::create([
            'sku'            => 'UI-TEST-01',
            'name'           => 'Keyboard Mechanical RGB Gaming',
            'unit'           => 'pcs',
            'purchase_price' => 500000,
            'sell_price'     => 750000,
            'price_online'   => 800000,
            'price_offline'  => 720000,
            'length_cm'      => 44.0,
            'width_cm'       => 14.0,
            'height_cm'      => 4.0,
            'weight_kg'      => 1.1,
            'min_stock'      => 5,
            'is_active'      => true,
        ]);

        $so = SalesOrder::create([
            'so_number'    => 'SO-TEST-CHAN-001',
            'customer_id'  => $this->customer->id,
            'channel'      => 'online',
            'user_id'      => $this->adminUser->id,
            'status'       => 'draft',
            'order_date'   => now()->toDateString(),
            'total_amount' => 888000,
        ]);

        // Check Product Index, Create, Show, Edit
        $this->actingAs($this->adminUser)->get(route('master.products.index'))
            ->assertOk()
            ->assertSee('UI-TEST-01')
            ->assertSee('Harga Online')
            ->assertSee('Harga Offline');

        $this->actingAs($this->adminUser)->get(route('master.products.create'))
            ->assertOk()
            ->assertSee('Harga Online (Rp)')
            ->assertSee('Dimensi & Berat Produk', false);

        $this->actingAs($this->adminUser)->get(route('master.products.show', $product))
            ->assertOk()
            ->assertSee('Harga Online:')
            ->assertSee('800.000')
            ->assertSee('Harga Offline:')
            ->assertSee('720.000')
            ->assertSee('Dimensi:', false)
            ->assertSee('44 cm', false)
            ->assertSee('14 cm', false)
            ->assertSee('4 cm', false)
            ->assertSee('1.1 kg', false);

        // Check Sales Order Index, Create, Show, Edit
        $this->actingAs($this->adminUser)->get(route('sales.orders.index'))
            ->assertOk()
            ->assertSee('SO-TEST-CHAN-001')
            ->assertSee('Online');

        $this->actingAs($this->adminUser)->get(route('sales.orders.create'))
            ->assertOk()
            ->assertSee('Channel Penjualan')
            ->assertSee('Offline')
            ->assertSee('Online');

        $this->actingAs($this->adminUser)->get(route('sales.orders.show', $so))
            ->assertOk()
            ->assertSee('SO-TEST-CHAN-001')
            ->assertSee('Online');

        $this->actingAs($this->adminUser)->get(route('sales.orders.edit', $so))
            ->assertOk()
            ->assertSee('Channel Penjualan');
    }
}
