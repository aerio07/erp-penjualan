<?php

namespace Tests\Feature;

use App\Enums\SalesOrderStatus;
use App\Models\ApprovalRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SalesOrderDiscountValidationTest extends TestCase
{
    use DatabaseTransactions;

    private User $sales;
    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = User::factory()->create(['role' => 'admin']);
        $this->customer = Customer::create([
            'code' => 'CUST-DISC',
            'name' => 'Customer Diskon',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-SO-DISC',
            'name' => 'Produk SO Diskon',
            'category' => 'General',
            'unit' => 'pcs',
            'purchase_price' => 100000,
            'sell_price' => 150000,
            'is_active' => true,
        ]);
    }

    public function test_cannot_create_so_if_discount_exceeds_subtotal(): void
    {
        // 10 pcs @ Rp 150.000 = Rp 1.500.000 subtotal
        // Input discount: Rp 2.000.000 (melebihi subtotal)
        $response = $this->actingAs($this->sales)->post(route('sales.orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 2000000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 150000,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('discount_amount');
        $this->assertEquals(0, SalesOrder::where('customer_id', $this->customer->id)->count());
    }

    public function test_can_create_so_with_normal_discount(): void
    {
        // 10 pcs @ Rp 150.000 = Rp 1.500.000 subtotal
        // Input discount: Rp 300.000 (wajar)
        // DPP = 1.200.000, PPN 11% = 132.000, Total = 1.332.000
        $response = $this->actingAs($this->sales)->post(route('sales.orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 300000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 150000,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $so = SalesOrder::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($so);
        $response->assertRedirect(route('sales.orders.index'));

        $this->assertEquals(300000, $so->discount_amount);
        $this->assertEquals(132000, $so->tax_amount);
        $this->assertEquals(1332000, $so->total_amount);
    }

    public function test_can_create_so_with_100_percent_discount(): void
    {
        // 10 pcs @ Rp 150.000 = Rp 1.500.000 subtotal
        // Input discount: Rp 1.500.000 (100%)
        // DPP = 0, PPN = 0, Total = 0
        $response = $this->actingAs($this->sales)->post(route('sales.orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 1500000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 150000,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $so = SalesOrder::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($so);
        $response->assertRedirect(route('sales.orders.index'));

        $this->assertEquals(1500000, $so->discount_amount);
        $this->assertEquals(0, $so->tax_amount);
        $this->assertEquals(0, $so->total_amount);
    }

    public function test_cannot_update_so_if_discount_exceeds_subtotal(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-TEST-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->sales->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 50000,
            'tax_amount' => 159500,
            'total_amount' => 1609500,
        ]);

        $response = $this->actingAs($this->sales)->put(route('sales.orders.update', $so), [
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 3000000, // melebihi subtotal 1.500.000
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 150000,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('discount_amount');

        // Pastikan nilai di database tidak berubah menjadi minus
        $so->refresh();
        $this->assertEquals(50000, $so->discount_amount);
        $this->assertEquals(1609500, $so->total_amount);
    }

    public function test_cancelling_so_cancels_pending_approval_request(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-TEST-002',
            'customer_id' => $this->customer->id,
            'user_id' => $this->sales->id,
            'status' => 'waiting_approval',
            'order_date' => now()->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 0,
            'tax_amount' => 1650000,
            'total_amount' => 60000000,
        ]);

        $approval = ApprovalRequest::create([
            'request_number' => 'APR-SO-TEST-01',
            'approvable_type' => SalesOrder::class,
            'approvable_id' => $so->id,
            'requester_id' => $this->sales->id,
            'amount' => 60000000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sales)->patch(route('sales.orders.cancel', $so));
        $response->assertSessionHas('success');

        $so->refresh();
        $this->assertEquals(SalesOrderStatus::Cancelled, $so->status);

        $approval->refresh();
        $this->assertEquals('rejected', $approval->status);
    }
}
