<?php

namespace Tests\Feature;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\ApprovalRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseOrderDiscountValidationTest extends TestCase
{
    use DatabaseTransactions;

    private User $purchasing;
    private Supplier $supplier;
    private Product $product;
    private Warehouse $warehouse;
    private PurchaseRequest $pr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purchasing = User::factory()->create(['role' => 'purchasing']);
        $this->supplier = Supplier::create([
            'code' => 'SUP-DISC',
            'name' => 'Supplier Diskon',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-DISC-01',
            'name' => 'Produk Uji Diskon',
            'category' => 'General',
            'unit' => 'pcs',
            'purchase_price' => 100000,
            'sell_price' => 150000,
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-DISC',
            'name' => 'Gudang Diskon',
            'is_active' => true,
        ]);

        $this->pr = PurchaseRequest::create([
            'pr_number' => 'PR-DISC-01',
            'user_id' => $this->purchasing->id,
            'warehouse_id' => $this->warehouse->id,
            'request_date' => now()->toDateString(),
            'required_date' => now()->addDays(7)->toDateString(),
            'status' => PurchaseRequestStatus::Confirmed,
        ]);
        $this->pr->items()->create([
            'product_id' => $this->product->id,
            'qty' => 10,
        ]);
    }

    public function test_cannot_create_po_if_discount_exceeds_subtotal(): void
    {
        // 10 pcs @ Rp 100.000 = Rp 1.000.000 subtotal
        // Input discount: Rp 1.500.000 (melebihi subtotal)
        $response = $this->actingAs($this->purchasing)->post(route('purchase.orders.store'), [
            'purchase_request_id' => $this->pr->id,
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(5)->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 1500000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 100000,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('discount_amount');
        $this->assertEquals(0, PurchaseOrder::count());
    }

    public function test_expected_date_is_required_when_creating_po(): void
    {
        $response = $this->actingAs($this->purchasing)->post(route('purchase.orders.store'), [
            'purchase_request_id' => $this->pr->id,
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'expected_date' => null,
            'tax_rate' => 11,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 100000,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('expected_date');
        $this->assertEquals(0, PurchaseOrder::count());
    }

    public function test_can_create_po_with_normal_discount(): void
    {
        // 10 pcs @ Rp 100.000 = Rp 1.000.000 subtotal
        // Input discount: Rp 200.000 (wajar)
        // DPP = 800.000, PPN 11% = 88.000, Total = 888.000
        $response = $this->actingAs($this->purchasing)->post(route('purchase.orders.store'), [
            'purchase_request_id' => $this->pr->id,
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(5)->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 200000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 100000,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase.orders.index'));

        $po = PurchaseOrder::first();
        $this->assertNotNull($po);
        $this->assertEquals(200000, $po->discount_amount);
        $this->assertEquals(88000, $po->tax_amount);
        $this->assertEquals(888000, $po->total_amount);
    }

    public function test_can_create_po_with_100_percent_discount(): void
    {
        // 10 pcs @ Rp 100.000 = Rp 1.000.000 subtotal
        // Input discount: Rp 1.000.000 (100%)
        // DPP = 0, PPN = 0, Total = 0
        $response = $this->actingAs($this->purchasing)->post(route('purchase.orders.store'), [
            'purchase_request_id' => $this->pr->id,
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(5)->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 1000000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 100000,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase.orders.index'));

        $po = PurchaseOrder::first();
        $this->assertNotNull($po);
        $this->assertEquals(1000000, $po->discount_amount);
        $this->assertEquals(0, $po->tax_amount);
        $this->assertEquals(0, $po->total_amount);
    }

    public function test_cannot_update_po_if_discount_exceeds_subtotal(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->purchasing->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(5)->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 50000,
            'tax_amount' => 104500,
            'total_amount' => 1054500,
        ]);

        $response = $this->actingAs($this->purchasing)->put(route('purchase.orders.update', $po), [
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(5)->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 2000000, // melebihi subtotal 1.000.000
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 100000,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('discount_amount');

        // Pastikan nilai di database tidak berubah menjadi minus
        $po->refresh();
        $this->assertEquals(50000, $po->discount_amount);
        $this->assertEquals(1054500, $po->total_amount);
    }

    public function test_cancelling_po_cancels_pending_approval_request(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-002',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->purchasing->id,
            'status' => 'waiting_approval',
            'order_date' => now()->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 0,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
        ]);

        $approval = ApprovalRequest::create([
            'request_number' => 'APR-TEST-01',
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $po->id,
            'requester_id' => $this->purchasing->id,
            'amount' => 1110000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->purchasing)->patch(route('purchase.orders.cancel', $po), [
            'cancel_reason' => 'Batal order karena salah input',
        ]);
        $response->assertSessionHas('success');

        $po->refresh();
        $this->assertEquals(PurchaseOrderStatus::Cancelled, $po->status);

        $approval->refresh();
        $this->assertEquals('rejected', $approval->status);
    }

    public function test_cannot_cancel_confirmed_po(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-CONFIRMED',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->purchasing->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 0,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
        ]);

        $response = $this->actingAs($this->purchasing)->patch(route('purchase.orders.cancel', $po), [
            'cancel_reason' => 'Batal order',
        ]);
        $response->assertStatus(403);

        $po->refresh();
        $this->assertEquals(PurchaseOrderStatus::Confirmed, $po->status);
    }
}
