<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $sales;
    private Customer $customer;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->sales = User::factory()->create(['role' => 'sales']);

        $this->customer = Customer::create([
            'code'      => 'CUST-001',
            'name'      => 'Customer Test',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'sku'            => 'PROD-SO-APP-01',
            'name'           => 'Barang Uji Approval SO',
            'category'       => 'Test',
            'unit'           => 'pcs',
            'purchase_price' => 50000,
            'sell_price'     => 100000,
            'min_stock'      => 5,
            'is_active'      => true,
        ]);

        $this->warehouse = Warehouse::create([
            'code'      => 'WH-APP-01',
            'name'      => 'Gudang Utama Approval',
            'is_active' => true,
        ]);
    }

    public function test_all_sales_orders_require_approval_regardless_of_amount(): void
    {
        // Nilai transaksi kecil (hanya Rp 100.000, jauh di bawah 10jt / 50jt)
        $so = SalesOrder::create([
            'so_number'       => 'SO-SMALL-001',
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->sales->id,
            'status'          => 'draft',
            'order_date'      => now()->toDateString(),
            'tax_rate'        => 0,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'total_amount'    => 100000,
        ]);

        SalesOrderItem::create([
            'sales_order_id'   => $so->id,
            'product_id'       => $this->product->id,
            'qty_ordered'      => 1,
            'unit_price'       => 100000,
            'discount_percent' => 0,
            'discount_amount'  => 0,
            'subtotal'         => 100000,
        ]);

        $response = $this->actingAs($this->sales)->patch(route('sales.orders.confirm', $so));

        $response->assertSessionHas('info', "Sales Order #{$so->so_number} telah dikirim untuk approval.");

        $so->refresh();
        $this->assertEquals('waiting_approval', $so->status);

        $approval = ApprovalRequest::where('approvable_type', SalesOrder::class)
            ->where('approvable_id', $so->id)
            ->first();

        $this->assertNotNull($approval);
        $this->assertEquals('pending', $approval->status);
        $this->assertEquals(100000, $approval->amount);
    }

    public function test_approving_sales_order_allocates_stock_and_confirms_so(): void
    {
        // Masukkan stok ke gudang: 10 pcs
        StockMovement::create([
            'product_id'    => $this->product->id,
            'warehouse_id'  => $this->warehouse->id,
            'type'          => 'in',
            'quantity'      => 10,
            'unit_cost'     => 50000,
            'movement_date' => now()->toDateString(),
            'user_id'       => $this->admin->id,
        ]);

        $so = SalesOrder::create([
            'so_number'       => 'SO-APPROVE-001',
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->sales->id,
            'status'          => 'draft',
            'order_date'      => now()->toDateString(),
            'tax_rate'        => 0,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'total_amount'    => 500000,
        ]);

        SalesOrderItem::create([
            'sales_order_id'   => $so->id,
            'product_id'       => $this->product->id,
            'qty_ordered'      => 5,
            'unit_price'       => 100000,
            'discount_percent' => 0,
            'discount_amount'  => 0,
            'subtotal'         => 500000,
        ]);

        // 1. Confirm SO -> status waiting_approval
        $this->actingAs($this->sales)->patch(route('sales.orders.confirm', $so));
        $so->refresh();
        $this->assertEquals('waiting_approval', $so->status);

        // 2. Admin approve via approvals.approve
        $approval = ApprovalRequest::where('approvable_type', SalesOrder::class)
            ->where('approvable_id', $so->id)
            ->first();

        $approveResponse = $this->actingAs($this->admin)->patch(route('approvals.approve', $approval));
        $approveResponse->assertSessionHas('success');

        $so->refresh();
        $this->assertEquals('confirmed', $so->status);
        $this->assertEquals('ready_to_ship', $so->fulfillment_status);
        $this->assertTrue($so->canCreateDelivery());
    }

    public function test_rejecting_sales_order_cancels_it_and_rejection_reason_is_optional(): void
    {
        $so = SalesOrder::create([
            'so_number'       => 'SO-REJECT-001',
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->sales->id,
            'status'          => 'waiting_approval',
            'order_date'      => now()->toDateString(),
            'tax_rate'        => 0,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'total_amount'    => 500000,
        ]);

        $approval = ApprovalRequest::create([
            'request_number'  => 'APR-SO-001',
            'approvable_type' => SalesOrder::class,
            'approvable_id'   => $so->id,
            'requester_id'    => $this->sales->id,
            'amount'          => 500000,
            'status'          => 'pending',
        ]);

        // Rejection without note is valid (optional)
        $response = $this->actingAs($this->admin)->patch(route('approvals.reject', $approval), [
            'rejection_reason' => null,
        ]);

        $response->assertSessionHas('success');

        $so->refresh();
        $this->assertEquals('cancelled', $so->status);

        $approval->refresh();
        $this->assertEquals('rejected', $approval->status);
        $this->assertNull($approval->rejection_reason);
    }
}
