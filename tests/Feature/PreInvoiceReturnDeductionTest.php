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

class PreInvoiceReturnDeductionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Supplier $supplier;
    protected Customer $customer;
    protected Warehouse $warehouse;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->supplier = Supplier::create([
            'name' => 'Supplier Test',
            'code' => 'SUP-TEST',
            'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'name' => 'Customer Test',
            'code' => 'CUST-TEST',
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'name' => 'Gudang Utama',
            'code' => 'WH-TEST',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'name' => 'Barang Uji',
            'sku' => 'SKU-UJI-01',
            'purchase_price' => 100000,
            'selling_price' => 150000,
            'stock' => 0,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
    }

    /**
     * Test Purchase: Retur dilakukan SEBELUM Invoice terbit.
     * PO 10 unit @ 100.000 (Total Rp 1.000.000).
     * LPB 10 unit diterima.
     * Retur Pembelian 2 unit selesai dikembalikan ke supplier.
     * Saat terbitkan Invoice Pembelian, sistem HANYA menagih 8 unit (DPP Rp 800.000).
     */
    public function test_purchase_invoice_only_bills_net_received_qty_when_pre_invoice_return_exists(): void
    {
        // Stok awal masuk
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 100000,
            'movement_date' => now()->toDateString(),
            'notes' => 'Stock in',
            'user_id' => $this->admin->id,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-PRE-RET-01',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 1000000,
        ]);
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 100000,
            'subtotal' => 1000000,
        ]);
        $grn = GoodsReceipt::create([
            'receipt_number' => 'LPB-PRE-RET-01',
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'received_date' => now()->toDateString(),
        ]);
        $grnItem = GoodsReceiptItem::create([
            'goods_receipt_id' => $grn->id,
            'purchase_order_item_id' => $poItem->id,
            'qty_received' => 10,
            'qty_rejected' => 0,
            'unit_cost' => 100000,
        ]);

        // Retur 2 unit diselesaikan SEBELUM invoice terbit
        $pRet = PurchaseReturn::create([
            'return_number' => 'RET-P-PRE-01',
            'goods_receipt_id' => $grn->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'draft',
            'return_date' => now()->toDateString(),
        ]);
        PurchaseReturnItem::create([
            'purchase_return_id' => $pRet->id,
            'goods_receipt_item_id' => $grnItem->id,
            'product_id' => $this->product->id,
            'source_type' => 'accepted',
            'qty' => 2,
            'unit_cost' => 100000,
        ]);
        $this->actingAs($this->admin)->patch(route('purchase.returns.complete', $pRet));

        // Buka form create invoice
        $createRes = $this->actingAs($this->admin)->get(route('purchase.invoices.create', [
            'po_id' => $po->id,
            'grn_id' => $grn->id,
        ]));
        $createRes->assertOk();

        // Submit form invoice
        $storeRes = $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id'  => $grn->id,
            'invoice_date'      => now()->toDateString(),
            'due_date'          => now()->addMonth()->toDateString(),
            'tax_rate'          => 0,
        ]);
        $storeRes->assertRedirect(route('purchase.invoices.index'));

        // Cek invoice yang terbentuk
        $invoice = PurchaseInvoice::with('items')->where('purchase_order_id', $po->id)->firstOrFail();
        $this->assertEquals(800000, $invoice->amount, 'DPP harus 800.000 untuk 8 unit bersih');
        $this->assertEquals(800000, $invoice->total_amount, 'Total invoice harus 800.000');
        $this->assertCount(1, $invoice->items);
        $this->assertEquals(8, $invoice->items->first()->qty_invoiced, 'Qty yang ditagih harus 8 unit');
        $this->assertEquals(800000, $invoice->items->first()->subtotal);

        // Pastikan LPB sudah ditandai ter-invoice
        $grn->refresh();
        $this->assertTrue((bool) $grn->is_invoiced);
    }

    /**
     * Test Sales: Retur dilakukan SEBELUM Invoice terbit.
     * SO 10 unit @ 150.000 (Total Rp 1.500.000).
     * SJ 10 unit dikirim.
     * Retur Penjualan 2 unit diterima kembali dari customer.
     * Saat terbitkan Invoice Penjualan, sistem HANYA menagih 8 unit (DPP Rp 1.200.000).
     */
    public function test_sales_invoice_only_bills_net_delivered_qty_when_pre_invoice_return_exists(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-PRE-RET-01',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 1500000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 150000,
            'subtotal' => 1500000,
        ]);
        $del = Delivery::create([
            'delivery_number' => 'SJ-PRE-RET-01',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        $delItem = DeliveryItem::create([
            'delivery_id' => $del->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 10,
        ]);

        // Retur Penjualan 2 unit diselesaikan SEBELUM invoice terbit
        $sRet = SalesReturn::create([
            'return_number' => 'RET-S-PRE-01',
            'delivery_id'   => $del->id,
            'customer_id'   => $this->customer->id,
            'status'        => 'draft',
            'return_date'   => now()->toDateString(),
        ]);
        SalesReturnItem::create([
            'sales_return_id'  => $sRet->id,
            'delivery_item_id' => $delItem->id,
            'product_id'       => $this->product->id,
            'qty'              => 2,
            'condition'        => 'baik',
        ]);
        $this->actingAs($this->admin)->patch(route('sales.returns.receive', $sRet));
        $this->actingAs($this->admin)->patch(route('sales.returns.complete', $sRet));

        // Submit form invoice
        $storeRes = $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $del->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addMonth()->toDateString(),
            'tax_rate'       => 0,
        ]);
        $storeRes->assertRedirect(route('sales.invoices.index'));

        // Cek invoice yang terbentuk
        $invoice = SalesInvoice::with('items')->where('sales_order_id', $so->id)->firstOrFail();
        $this->assertEquals(1200000, $invoice->amount, 'DPP harus 1.200.000 untuk 8 unit bersih');
        $this->assertEquals(1200000, $invoice->total_amount, 'Total invoice harus 1.200.000');
        $this->assertCount(1, $invoice->items);
        $this->assertEquals(8, $invoice->items->first()->qty_invoiced, 'Qty yang ditagih harus 8 unit');
        $this->assertEquals(1200000, $invoice->items->first()->subtotal);

        // Pastikan SJ sudah ditandai ter-invoice
        $del->refresh();
        $this->assertTrue((bool) $del->is_invoiced);
    }
}
