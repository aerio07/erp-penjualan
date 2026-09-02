<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceReturnConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Product $product;
    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->product = Product::create([
            'sku' => 'INV-RET-001',
            'name' => 'Produk Invoice Retur',
            'unit' => 'pcs',
            'purchase_price' => 60,
            'sell_price' => 100,
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-INVRET',
            'name' => 'Gudang Invoice Retur',
            'is_active' => true,
        ]);
    }

    public function test_sales_invoice_only_bills_delivered_qty_not_already_returned(): void
    {
        [$salesOrder] = $this->makeSalesDelivery(qty: 10);
        $deliveryItem = DeliveryItem::firstOrFail();

        $return = SalesReturn::create([
            'return_number' => 'SRET-PRE-001',
            'delivery_id' => $deliveryItem->delivery_id,
            'customer_id' => $salesOrder->customer_id,
            'return_date' => now()->toDateString(),
            'status' => 'received',
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $return->id,
            'product_id' => $this->product->id,
            'delivery_item_id' => $deliveryItem->id,
            'qty' => 4,
            'condition' => 'baik',
        ]);

        $response = $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $salesOrder->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $response->assertRedirect(route('sales.invoices.index'));

        $invoice = SalesInvoice::with('items')->firstOrFail();
        $this->assertEquals(600, $invoice->total_amount);
        $this->assertEquals(6, $invoice->items->sum('qty_invoiced'));
    }

    public function test_sales_return_after_invoice_reduces_outstanding_invoice(): void
    {
        [$salesOrder] = $this->makeSalesDelivery(qty: 10);

        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $salesOrder->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $invoice = SalesInvoice::with('items')->firstOrFail();
        $deliveryItem = DeliveryItem::firstOrFail();
        $return = SalesReturn::create([
            'return_number' => 'SRET-POST-001',
            'delivery_id' => $deliveryItem->delivery_id,
            'customer_id' => $salesOrder->customer_id,
            'return_date' => now()->toDateString(),
            'status' => 'received',
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $return->id,
            'product_id' => $this->product->id,
            'delivery_item_id' => $deliveryItem->id,
            'qty' => 4,
            'condition' => 'baik',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('sales.returns.complete', $return));
        $response->assertRedirect();

        $invoice->refresh()->load(['items', 'payments']);
        $this->assertEquals(400, $invoice->total_reversed_amount);
        $this->assertEquals(600, $invoice->effective_total_amount);
        $this->assertEquals(600, $invoice->outstanding_amount);
        $this->assertEquals('unpaid', $invoice->status);

        $report = $this->actingAs($this->admin)->get(route('accounting.reports.receivables'));
        $report->assertOk();
        $report->assertViewHas('totalOutstanding', 600);

        $paymentForm = $this->actingAs($this->admin)->get(route('sales.payments.create', ['invoice_id' => $invoice->id]));
        $paymentForm->assertOk();
        $paymentForm->assertSee('Sisa Piutang: Rp 600');
    }

    public function test_purchase_invoice_bills_full_received_qty_from_lpb(): void
    {
        [$purchaseOrder, $receipt] = $this->makePurchaseReceipt(qty: 10);
        $grnItem = GoodsReceiptItem::firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $purchaseOrder->id,
            'goods_receipt_id' => $receipt->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $response->assertRedirect(route('purchase.invoices.index'));

        $invoice = PurchaseInvoice::with('items')->firstOrFail();
        $this->assertEquals(600, $invoice->total_amount);
        $this->assertEquals(10, $invoice->items->sum('qty_invoiced'));
    }

    public function test_purchase_return_after_invoice_reduces_outstanding_invoice(): void
    {
        [$purchaseOrder, $receipt] = $this->makePurchaseReceipt(qty: 10);

        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $purchaseOrder->id,
            'goods_receipt_id' => $receipt->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $invoice = PurchaseInvoice::with('items')->firstOrFail();
        $grnItem = GoodsReceiptItem::firstOrFail();
        $return = PurchaseReturn::create([
            'return_number' => 'PRET-POST-001',
            'goods_receipt_id' => $grnItem->goods_receipt_id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'return_date' => now()->toDateString(),
            'status' => 'sent',
        ]);
        PurchaseReturnItem::create([
            'purchase_return_id' => $return->id,
            'product_id' => $this->product->id,
            'goods_receipt_item_id' => $grnItem->id,
            'source_type' => 'accepted',
            'qty' => 4,
            'unit_cost' => 60,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('purchase.returns.complete', $return));
        $response->assertRedirect();

        $invoice->refresh()->load(['items', 'payments']);
        $this->assertEquals(240, $invoice->total_reversed_amount);
        $this->assertEquals(360, $invoice->effective_total_amount);
        $this->assertEquals(360, $invoice->outstanding_amount);
        $this->assertEquals('unpaid', $invoice->status);

        $report = $this->actingAs($this->admin)->get(route('accounting.reports.payables'));
        $report->assertOk();
        $report->assertViewHas('totalOutstanding', 360);

        $paymentForm = $this->actingAs($this->admin)->get(route('purchase.payments.create', ['invoice_id' => $invoice->id]));
        $paymentForm->assertOk();
        $paymentForm->assertSee('Sisa Hutang: Rp 360');
    }

    public function test_sales_return_with_header_discount_and_tax_proration(): void
    {
        // 10 items @ 100 = 1000 subtotal, header discount = 200, tax 10%
        // DPP = 800, Tax = 80, Total Amount = 880
        $customer = Customer::create([
            'code' => 'CUST-DISCTAX',
            'name' => 'Customer Diskon Pajak',
            'is_active' => true,
        ]);
        $salesOrder = SalesOrder::create([
            'so_number' => 'SO-DISCTAX-001',
            'customer_id' => $customer->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'discount_amount' => 200,
            'tax_rate' => 10,
            'tax_amount' => 80,
            'total_amount' => 880,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
        ]);
        $delivery = Delivery::create([
            'delivery_number' => 'SJ-DISCTAX-001',
            'sales_order_id' => $salesOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        $delItem = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 10,
        ]);

        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $salesOrder->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 10,
        ]);

        $invoice = SalesInvoice::with('items')->firstOrFail();
        $this->assertEquals(800, $invoice->amount);
        $this->assertEquals(80, $invoice->tax_amount);
        $this->assertEquals(880, $invoice->total_amount);

        // Verify item-level proration
        $invItem = $invoice->items->first();
        $this->assertEquals(800, $invItem->subtotal);
        $this->assertEquals(80, $invItem->tax_amount);

        // Return 50% (5 pcs out of 10)
        $return = SalesReturn::create([
            'return_number' => 'SRET-DISCTAX-001',
            'delivery_id' => $delivery->id,
            'customer_id' => $customer->id,
            'return_date' => now()->toDateString(),
            'status' => 'received',
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $return->id,
            'product_id' => $this->product->id,
            'delivery_item_id' => $delItem->id,
            'qty' => 5,
            'condition' => 'baik',
        ]);

        $this->actingAs($this->admin)->patch(route('sales.returns.complete', $return));

        $invoice->refresh();
        // 50% of 880 is 440
        $this->assertEquals(440, $invoice->total_reversed_amount);
        $this->assertEquals(440, $invoice->effective_total_amount);
        $this->assertEquals(440, $invoice->outstanding_amount);
    }

    public function test_delivery_creation_fails_gracefully_when_stock_is_insufficient(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-NOSTOCK',
            'name' => 'Customer No Stock',
            'is_active' => true,
        ]);
        $salesOrder = SalesOrder::create([
            'so_number' => 'SO-NOSTOCK-001',
            'customer_id' => $customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'total_amount' => 1000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
        ]);

        // Stock in warehouse is 0 for this test
        $response = $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $salesOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 10,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    private function makeSalesDelivery(int $qty): array
    {
        $customer = Customer::create([
            'code' => 'CUST-INVRET',
            'name' => 'Customer Invoice Retur',
            'is_active' => true,
        ]);
        $salesOrder = SalesOrder::create([
            'so_number' => 'SO-INVRET-' . $qty . '-' . uniqid(),
            'customer_id' => $customer->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => $qty * 100,
        ]);
        $salesOrderItem = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $this->product->id,
            'qty_ordered' => $qty,
            'unit_price' => 100,
            'subtotal' => $qty * 100,
        ]);
        $delivery = Delivery::create([
            'delivery_number' => 'SJ-INVRET-' . $qty . '-' . uniqid(),
            'sales_order_id' => $salesOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $salesOrderItem->id,
            'qty_delivered' => $qty,
        ]);

        return [$salesOrder, $delivery];
    }

    private function makePurchaseReceipt(int $qty): array
    {
        $supplier = Supplier::create([
            'code' => 'SUP-INVRET',
            'name' => 'Supplier Invoice Retur',
            'is_active' => true,
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-INVRET-' . $qty . '-' . uniqid(),
            'supplier_id' => $supplier->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => $qty * 60,
        ]);
        $purchaseOrderItem = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $this->product->id,
            'qty_ordered' => $qty,
            'unit_price' => 60,
            'subtotal' => $qty * 60,
        ]);
        $receipt = GoodsReceipt::create([
            'receipt_number' => 'GRN-INVRET-' . $qty . '-' . uniqid(),
            'purchase_order_id' => $purchaseOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'qc_status' => 'passed',
            'received_date' => now()->toDateString(),
        ]);
        GoodsReceiptItem::create([
            'goods_receipt_id' => $receipt->id,
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_received' => $qty,
            'qty_rejected' => 0,
            'unit_cost' => 60,
        ]);

        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => $qty,
            'unit_cost' => 60,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        return [$purchaseOrder, $receipt];
    }
}
