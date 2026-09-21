<?php

namespace Tests\Feature;

use App\Enums\SalesInvoiceStatus;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesPayment;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceReturnAntiOverpaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Warehouse $warehouse;
    protected Customer $customer;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-AOP',
            'name' => 'Gudang Anti Overpayment',
            'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'code' => 'CUST-AOP',
            'name' => 'Customer Anti Overpayment',
            'is_active' => true,
        ]);
        $this->supplier = Supplier::create([
            'code' => 'SUPP-AOP',
            'name' => 'Supplier Anti Overpayment',
            'is_active' => true,
        ]);
    }

    private function createProduct(string $sku, string $name, float $price = 100000): Product
    {
        return Product::create([
            'sku' => $sku,
            'name' => $name,
            'unit' => 'pcs',
            'purchase_price' => $price,
            'sell_price' => $price,
            'is_active' => true,
        ]);
    }

    /**
     * 1 & 2. Test Kasus Dasar & Batas Pas pada Sales Return:
     * Invoice 10 unit @ Rp 100.000 (Rp 1.000.000, 0% tax), Bayar Rp 600.000 (Sisa Rp 400.000).
     * - Coba retur 5 unit (Rp 500.000) -> Ditolak (Maks 4 unit).
     * - Coba retur 4 unit (Rp 400.000) -> Berhasil, sisa tagihan jadi Rp 0.
     */
    public function test_sales_return_basic_and_exact_threshold_anti_overpayment(): void
    {
        $product = $this->createProduct('SKU-AOP-1', 'Barang Alpha 100k', 100000);

        // Buat SO, Delivery, Invoice
        $so = SalesOrder::create([
            'so_number' => 'SO-AOP-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'total_amount' => 1000000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $product->id,
            'qty_ordered' => 10,
            'unit_price' => 100000,
            'subtotal' => 1000000,
        ]);

        $delivery = Delivery::create([
            'delivery_number' => 'SJ-AOP-001',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
            'is_invoiced' => true,
        ]);
        $delItem = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 10,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'INV-AOP-001',
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'amount' => 1000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000000,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'partial',
        ]);
        $delivery->update(['sales_invoice_id' => $invoice->id]);

        $invItem = $invoice->items()->create([
            'sales_order_item_id' => $soItem->id,
            'delivery_item_id' => $delItem->id,
            'product_id' => $product->id,
            'qty_invoiced' => 10,
            'reversed_qty' => 0,
            'unit_price' => 100000,
            'subtotal' => 1000000,
            'tax_amount' => 0,
            'cogs_amount' => 1000000,
        ]);

        // Bayar sebagian: Rp 600.000 -> sisa Rp 400.000
        SalesPayment::create([
            'payment_number' => 'PAY-AOP-001',
            'sales_invoice_id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => 600000,
            'status' => 'posted',
            'payment_method' => 'bank_transfer',
            'user_id' => $this->admin->id,
        ]);

        $invoice->refresh();
        $this->assertEquals(600000, $invoice->total_paid);
        $this->assertEquals(400000, $invoice->outstanding_amount);

        // A. Coba retur 5 unit (Rp 500.000) -> Harus DITOLAK
        $responseReject = $this->actingAs($this->admin)->post(route('sales.returns.store'), [
            'delivery_id' => $delivery->id,
            'return_date' => now()->toDateString(),
            'items' => [
                [
                    'delivery_item_id' => $delItem->id,
                    'product_id' => $product->id,
                    'qty' => 5,
                    'condition' => 'baik',
                ]
            ]
        ]);
        $responseReject->assertSessionHasErrors('items');
        $this->assertDatabaseMissing('sales_returns', ['delivery_id' => $delivery->id]);

        // B. Coba retur 4 unit (Rp 400.000) -> Harus BERHASIL
        $responseOk = $this->actingAs($this->admin)->post(route('sales.returns.store'), [
            'delivery_id' => $delivery->id,
            'return_date' => now()->toDateString(),
            'items' => [
                [
                    'delivery_item_id' => $delItem->id,
                    'product_id' => $product->id,
                    'qty' => 4,
                    'condition' => 'baik',
                ]
            ]
        ]);
        $responseOk->assertRedirect(route('sales.returns.index'));
        $this->assertDatabaseHas('sales_returns', ['delivery_id' => $delivery->id]);

        $return = SalesReturn::where('delivery_id', $delivery->id)->first();
        $return->update(['status' => 'received']);
        $this->actingAs($this->admin)->patch(route('sales.returns.complete', $return));

        $invoice->refresh();
        $this->assertEquals(400000, $invoice->total_reversed_amount);
        $this->assertEquals(600000, $invoice->effective_total_amount);
        $this->assertEquals(0, $invoice->outstanding_amount);
        $this->assertEquals(SalesInvoiceStatus::Paid, $invoice->status);
    }

    /**
     * 3. Test Kasus Sisa Ganjil (FLOORing):
     * Invoice 10 unit @ Rp 100.000, bayar Rp 650.000 (sisa Rp 350.000).
     * floor(350k / 100k) = 3 unit. Coba retur 4 -> gagal, retur 3 -> lolos.
     */
    public function test_sales_return_odd_remainder_flooring(): void
    {
        $product = $this->createProduct('SKU-AOP-FLOOR', 'Barang Floor 100k', 100000);

        $so = SalesOrder::create([
            'so_number' => 'SO-AOP-002',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'total_amount' => 1000000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $product->id,
            'qty_ordered' => 10,
            'unit_price' => 100000,
            'subtotal' => 1000000,
        ]);

        $delivery = Delivery::create([
            'delivery_number' => 'SJ-AOP-002',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
            'is_invoiced' => true,
        ]);
        $delItem = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 10,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'INV-AOP-002',
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'amount' => 1000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000000,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'partial',
        ]);
        $delivery->update(['sales_invoice_id' => $invoice->id]);

        $invoice->items()->create([
            'sales_order_item_id' => $soItem->id,
            'delivery_item_id' => $delItem->id,
            'product_id' => $product->id,
            'qty_invoiced' => 10,
            'reversed_qty' => 0,
            'unit_price' => 100000,
            'subtotal' => 1000000,
            'tax_amount' => 0,
            'cogs_amount' => 1000000,
        ]);

        SalesPayment::create([
            'payment_number' => 'PAY-AOP-002',
            'sales_invoice_id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => 650000,
            'status' => 'posted',
            'payment_method' => 'bank_transfer',
            'user_id' => $this->admin->id,
        ]);

        $invoice->refresh();
        $this->assertEquals(350000, $invoice->outstanding_amount);

        // Retur 4 unit (Rp 400.000 > Rp 350.000) -> GAGAL
        $responseReject = $this->actingAs($this->admin)->post(route('sales.returns.store'), [
            'delivery_id' => $delivery->id,
            'return_date' => now()->toDateString(),
            'items' => [
                ['delivery_item_id' => $delItem->id, 'product_id' => $product->id, 'qty' => 4, 'condition' => 'baik']
            ]
        ]);
        $responseReject->assertSessionHasErrors('items');

        // Retur 3 unit (Rp 300.000 <= Rp 350.000) -> LOLOS
        $responseOk = $this->actingAs($this->admin)->post(route('sales.returns.store'), [
            'delivery_id' => $delivery->id,
            'return_date' => now()->toDateString(),
            'items' => [
                ['delivery_item_id' => $delItem->id, 'product_id' => $product->id, 'qty' => 3, 'condition' => 'baik']
            ]
        ]);
        $responseOk->assertRedirect(route('sales.returns.index'));
    }

    /**
     * 4 & 5. Test Pre-Payment (Belum Bayar) & Pre-Invoice:
     * - Belum bayar: retur fisik penuh lolos.
     * - Pre-invoice ($invoice == null): retur fisik penuh lolos.
     */
    public function test_sales_return_pre_payment_and_pre_invoice(): void
    {
        $product = $this->createProduct('SKU-AOP-PRE', 'Barang Pre 100k', 100000);

        $so = SalesOrder::create([
            'so_number' => 'SO-AOP-003',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'total_amount' => 500000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $product->id,
            'qty_ordered' => 5,
            'unit_price' => 100000,
            'subtotal' => 500000,
        ]);

        $delivery = Delivery::create([
            'delivery_number' => 'SJ-AOP-003',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
            'is_invoiced' => false,
        ]);
        $delItem = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 5,
        ]);

        // Pre-invoice return 5 unit -> Harus BERHASIL
        $responsePreInv = $this->actingAs($this->admin)->post(route('sales.returns.store'), [
            'delivery_id' => $delivery->id,
            'return_date' => now()->toDateString(),
            'items' => [
                ['delivery_item_id' => $delItem->id, 'product_id' => $product->id, 'qty' => 5, 'condition' => 'baik']
            ]
        ]);
        $responsePreInv->assertRedirect(route('sales.returns.index'));
    }

    /**
     * 6. Test Invoice Lunas 100%:
     * Jika invoice sudah lunas dibayar penuh, coba retur berapa pun harus DITOLAK
     * dengan pesan edukatif bahwa invoice sudah lunas.
     */
    public function test_sales_return_rejected_when_invoice_fully_paid(): void
    {
        $product = $this->createProduct('SKU-AOP-LUNAS', 'Barang Lunas 100k', 100000);

        $so = SalesOrder::create([
            'so_number' => 'SO-AOP-004',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'total_amount' => 500000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $product->id,
            'qty_ordered' => 5,
            'unit_price' => 100000,
            'subtotal' => 500000,
        ]);

        $delivery = Delivery::create([
            'delivery_number' => 'SJ-AOP-004',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
            'is_invoiced' => true,
        ]);
        $delItem = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 5,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'INV-AOP-004',
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'amount' => 500000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 500000,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'paid',
        ]);
        $delivery->update(['sales_invoice_id' => $invoice->id]);

        $invoice->items()->create([
            'sales_order_item_id' => $soItem->id,
            'delivery_item_id' => $delItem->id,
            'product_id' => $product->id,
            'qty_invoiced' => 5,
            'reversed_qty' => 0,
            'unit_price' => 100000,
            'subtotal' => 500000,
            'tax_amount' => 0,
            'cogs_amount' => 500000,
        ]);

        SalesPayment::create([
            'payment_number' => 'PAY-AOP-004',
            'sales_invoice_id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500000,
            'status' => 'posted',
            'payment_method' => 'bank_transfer',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('sales.returns.store'), [
            'delivery_id' => $delivery->id,
            'return_date' => now()->toDateString(),
            'items' => [
                ['delivery_item_id' => $delItem->id, 'product_id' => $product->id, 'qty' => 1, 'condition' => 'baik']
            ]
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertTrue(str_contains(session('errors')->first('items'), 'sudah lunas dibayar penuh'));
    }

    /**
     * 7. TEST MULTI-ITEM OVERPAYMENT (KRITIS):
     * Invoice 2 produk: Produk A (50k) dan Produk B (50k).
     * Total tagihan = Rp 1.000.000 (10 A + 10 B).
     * Customer bayar Rp 800.000 -> Sisa belum bayar Rp 200.000.
     * Batas per item:
     * - Produk A: floor(200k / 50k) = 4 unit
     * - Produk B: floor(200k / 50k) = 4 unit
     * User submit: Produk A = 3 unit + Produk B = 3 unit (Total Rp 300.000).
     * Masing-masing produk lolos batas individual (3 <= 4),
     * TAPI TOTAL AGREGAT Rp 300.000 > Rp 200.000 -> HARUS DITOLAK!
     */
    public function test_multi_item_overpayment_aggregate_check(): void
    {
        $prodA = $this->createProduct('SKU-MULTI-A', 'Produk Multi A', 50000);
        $prodB = $this->createProduct('SKU-MULTI-B', 'Produk Multi B', 50000);

        $so = SalesOrder::create([
            'so_number' => 'SO-AOP-MULTI',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'total_amount' => 1000000,
        ]);
        $soItemA = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $prodA->id,
            'qty_ordered' => 10,
            'unit_price' => 50000,
            'subtotal' => 500000,
        ]);
        $soItemB = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $prodB->id,
            'qty_ordered' => 10,
            'unit_price' => 50000,
            'subtotal' => 500000,
        ]);

        $delivery = Delivery::create([
            'delivery_number' => 'SJ-AOP-MULTI',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
            'is_invoiced' => true,
        ]);
        $delItemA = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItemA->id,
            'qty_delivered' => 10,
        ]);
        $delItemB = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItemB->id,
            'qty_delivered' => 10,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'INV-AOP-MULTI',
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'amount' => 1000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000000,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'partial',
        ]);
        $delivery->update(['sales_invoice_id' => $invoice->id]);

        $invoice->items()->create([
            'sales_order_item_id' => $soItemA->id,
            'delivery_item_id' => $delItemA->id,
            'product_id' => $prodA->id,
            'qty_invoiced' => 10,
            'reversed_qty' => 0,
            'unit_price' => 50000,
            'subtotal' => 500000,
            'tax_amount' => 0,
            'cogs_amount' => 500000,
        ]);
        $invoice->items()->create([
            'sales_order_item_id' => $soItemB->id,
            'delivery_item_id' => $delItemB->id,
            'product_id' => $prodB->id,
            'qty_invoiced' => 10,
            'reversed_qty' => 0,
            'unit_price' => 50000,
            'subtotal' => 500000,
            'tax_amount' => 0,
            'cogs_amount' => 500000,
        ]);

        // Bayar Rp 800.000 -> Sisa belum bayar Rp 200.000
        SalesPayment::create([
            'payment_number' => 'PAY-AOP-MULTI',
            'sales_invoice_id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => 800000,
            'status' => 'posted',
            'payment_method' => 'bank_transfer',
            'user_id' => $this->admin->id,
        ]);

        $invoice->refresh();
        $this->assertEquals(200000, $invoice->outstanding_amount);

        // Submit A = 3 unit + B = 3 unit (Total Rp 300.000 > Rp 200.000)
        $response = $this->actingAs($this->admin)->post(route('sales.returns.store'), [
            'delivery_id' => $delivery->id,
            'return_date' => now()->toDateString(),
            'items' => [
                ['delivery_item_id' => $delItemA->id, 'product_id' => $prodA->id, 'qty' => 3, 'condition' => 'baik'],
                ['delivery_item_id' => $delItemB->id, 'product_id' => $prodB->id, 'qty' => 3, 'condition' => 'baik'],
            ]
        ]);

        // Harus DITOLAK karena total nilai agregat tembus
        $response->assertSessionHasErrors('items');
        $this->assertTrue(str_contains(session('errors')->first('items'), 'Total nilai retur'));
        $this->assertDatabaseMissing('sales_returns', ['delivery_id' => $delivery->id]);

        // Submit A = 2 unit + B = 2 unit (Total Rp 200.000 == Rp 200.000) -> Harus BERHASIL
        $responseOk = $this->actingAs($this->admin)->post(route('sales.returns.store'), [
            'delivery_id' => $delivery->id,
            'return_date' => now()->toDateString(),
            'items' => [
                ['delivery_item_id' => $delItemA->id, 'product_id' => $prodA->id, 'qty' => 2, 'condition' => 'baik'],
                ['delivery_item_id' => $delItemB->id, 'product_id' => $prodB->id, 'qty' => 2, 'condition' => 'baik'],
            ]
        ]);
        $responseOk->assertRedirect(route('sales.returns.index'));
        $this->assertDatabaseHas('sales_returns', ['delivery_id' => $delivery->id]);
    }

    /**
     * 8. Test Simetris Purchase Return:
     * - Coba retur melebihi sisa hutang -> Ditolak
     * - Coba retur saat invoice lunas penuh -> Ditolak
     * - Multi-item retur melebihi sisa hutang -> Ditolak
     */
    public function test_purchase_return_anti_overpayment_symmetry(): void
    {
        $product = $this->createProduct('SKU-PO-AOP', 'Barang PO AOP 100k', 100000);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-AOP-001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'total_amount' => 1000000,
        ]);
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'qty_ordered' => 10,
            'unit_price' => 100000,
            'subtotal' => 1000000,
        ]);

        $grn = GoodsReceipt::create([
            'receipt_number' => 'LPB-AOP-001',
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'qc_status' => 'passed',
            'received_date' => now()->toDateString(),
            'is_invoiced' => true,
        ]);
        $grnItem = GoodsReceiptItem::create([
            'goods_receipt_id' => $grn->id,
            'purchase_order_item_id' => $poItem->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_received' => 10,
            'qty_rejected' => 0,
            'unit_cost' => 100000,
            'condition' => 'baik',
        ]);

        app(\App\Services\StockService::class)->recordMovement([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 100000,
            'movement_date' => now()->toDateString(),
            'notes' => 'Penerimaan LPB Test',
            'user_id' => $this->admin->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'PINV-AOP-001',
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'amount' => 1000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000000,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'partial',
        ]);
        $grn->update(['purchase_invoice_id' => $invoice->id]);

        $invoice->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'goods_receipt_item_id' => $grnItem->id,
            'product_id' => $product->id,
            'qty_invoiced' => 10,
            'reversed_qty' => 0,
            'unit_price' => 100000,
            'subtotal' => 1000000,
            'tax_amount' => 0,
        ]);

        // Bayar sebagian: Rp 600.000 -> sisa Rp 400.000
        PurchasePayment::create([
            'payment_number' => 'PPAY-AOP-001',
            'purchase_invoice_id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => 600000,
            'status' => 'posted',
            'payment_method' => 'bank_transfer',
            'user_id' => $this->admin->id,
        ]);

        $invoice->refresh();
        $this->assertEquals(400000, $invoice->outstanding_amount);

        // A. Coba retur 5 unit (Rp 500.000 > Rp 400.000) -> DITOLAK
        $responseReject = $this->actingAs($this->admin)->post(route('purchase.returns.store'), [
            'goods_receipt_id' => $grn->id,
            'return_date' => now()->toDateString(),
            'items' => [
                [
                    'goods_receipt_item_id' => $grnItem->id,
                    'product_id' => $product->id,
                    'source_type' => 'accepted',
                    'qty' => 5,
                    'unit_cost' => 100000,
                ]
            ]
        ]);
        $responseReject->assertSessionHasErrors('items');

        // B. Coba retur 4 unit (Rp 400.000 == Rp 400.000) -> BERHASIL
        $responseOk = $this->actingAs($this->admin)->post(route('purchase.returns.store'), [
            'goods_receipt_id' => $grn->id,
            'return_date' => now()->toDateString(),
            'items' => [
                [
                    'goods_receipt_item_id' => $grnItem->id,
                    'product_id' => $product->id,
                    'source_type' => 'accepted',
                    'qty' => 4,
                    'unit_cost' => 100000,
                ]
            ]
        ]);
        $responseOk->assertRedirect(route('purchase.returns.index'));
        $this->assertDatabaseHas('purchase_returns', ['goods_receipt_id' => $grn->id]);
    }
}
