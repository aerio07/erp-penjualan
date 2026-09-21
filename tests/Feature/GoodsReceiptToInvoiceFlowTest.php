<?php

namespace Tests\Feature;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsReceiptToInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Warehouse $warehouse;
    protected Supplier $supplier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-GRN-TEST',
            'name' => 'Gudang Utama GRN',
            'is_active' => true,
        ]);
        $this->supplier = Supplier::create([
            'code' => 'SUPP-GRN-TEST',
            'name' => 'PT Supplier Utama',
            'payment_term' => 30,
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-GRN-1',
            'name' => 'Bahan Baku A',
            'unit' => 'pcs',
            'purchase_price' => 50000,
            'sell_price' => 70000,
            'is_active' => true,
        ]);
    }

    private function createPurchaseOrder(int $qty = 10, float $taxRate = 11): PurchaseOrder
    {
        $unitPrice = 50000;
        $subtotal = $qty * $unitPrice;
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $totalAmount = $subtotal + $taxAmount;

        $po = PurchaseOrder::create([
            'po_number'    => 'PO-GRN-' . uniqid(),
            'supplier_id'  => $this->supplier->id,
            'user_id'      => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'confirmed',
            'order_date'   => now()->subDays(2)->toDateString(),
            'discount_amount' => 0,
            'tax_rate'     => $taxRate,
            'tax_amount'   => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id'        => $this->product->id,
            'qty_ordered'       => $qty,
            'qty_received'      => 0,
            'unit_price'        => $unitPrice,
            'discount_percent'  => 0,
            'discount_amount'   => 0,
            'subtotal'          => $subtotal,
        ]);

        return $po;
    }

    public function test_instant_goods_receipt_creation_auto_generates_invoice_and_posts_journal(): void
    {
        $po = $this->createPurchaseOrder(10);
        $poItem = $po->items->first();

        $response = $this->actingAs($this->admin)
            ->post(route('purchase.goods-receipts.store'), [
                'purchase_order_id'       => $po->id,
                'warehouse_id'            => $this->warehouse->id,
                'received_date'           => '2026-09-02',
                'auto_generate_invoice'   => '1',
                'supplier_invoice_number' => 'INV-SUPP-9988',
                'invoice_date'            => '2026-09-02',
                'due_date'                => '2026-10-02',
                'invoice_tax_rate'        => 11,
                'items' => [
                    [
                        'purchase_order_item_id' => $poItem->id,
                        'warehouse_id'           => $this->warehouse->id,
                        'qty_physical'           => 10,
                        'qty_received'           => 10,
                        'qty_rejected'           => 0,
                        'condition'              => 'Good',
                        'shortage_reason'        => 'none',
                    ]
                ]
            ]);

        $response->assertRedirect(route('purchase.goods-receipts.index'));
        $response->assertSessionHas('success');

        // Pastikan GRN tersimpan dan disetujui
        $grn = GoodsReceipt::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($grn);
        $this->submitAndApproveGoodsReceipt($grn);
        $this->assertTrue((bool) $grn->is_invoiced);
        $this->assertNotNull($grn->purchase_invoice_id);

        // Pastikan PurchaseInvoice terbuat
        $invoice = PurchaseInvoice::find($grn->purchase_invoice_id);
        $this->assertNotNull($invoice);
        $this->assertEquals('INV-SUPP-9988', $invoice->supplier_invoice_number);
        $this->assertEquals('2026-09-02', $invoice->invoice_date->toDateString());
        $this->assertEquals('2026-10-02', $invoice->due_date->toDateString());
        $this->assertEquals(500000, (float) $invoice->amount);
        $this->assertEquals(55000, (float) $invoice->tax_amount);
        $this->assertEquals(555000, (float) $invoice->total_amount);
        $this->assertEquals(PurchaseInvoiceStatus::Unpaid, $invoice->status);

        // Pastikan Jurnal otomatis terposting
        $journal = JournalEntry::where('reference_type', PurchaseInvoice::class)
            ->where('reference_id', $invoice->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertEquals('posted', $journal->status);
    }

    public function test_staged_goods_receipt_creation_can_be_invoiced_afterwards(): void
    {
        $po = $this->createPurchaseOrder(5);
        $poItem = $po->items->first();

        // Buat GRN tanpa opsi auto invoice (bertahap)
        $response = $this->actingAs($this->admin)
            ->post(route('purchase.goods-receipts.store'), [
                'purchase_order_id'     => $po->id,
                'warehouse_id'          => $this->warehouse->id,
                'received_date'         => '2026-09-02',
                'auto_generate_invoice' => '0',
                'items' => [
                    [
                        'purchase_order_item_id' => $poItem->id,
                        'warehouse_id'           => $this->warehouse->id,
                        'qty_physical'           => 5,
                        'qty_received'           => 5,
                        'qty_rejected'           => 0,
                        'condition'              => 'Good',
                        'shortage_reason'        => 'none',
                    ]
                ]
            ]);

        $response->assertRedirect(route('purchase.goods-receipts.index'));
        $grn = GoodsReceipt::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($grn);
        $this->submitAndApproveGoodsReceipt($grn);
        $this->assertFalse((bool) $grn->is_invoiced);
        $this->assertNull($grn->purchase_invoice_id);

        // Terbitkan invoice secara terpisah
        $invResponse = $this->actingAs($this->admin)
            ->post(route('purchase.invoices.store'), [
                'purchase_order_id'       => $po->id,
                'goods_receipt_id'        => $grn->id,
                'supplier_invoice_number' => 'INV-LATER-01',
                'invoice_date'            => '2026-09-03',
                'due_date'                => '2026-10-03',
                'tax_rate'                => 11,
            ]);

        $invResponse->assertRedirect(route('purchase.invoices.index'));
        $grn->refresh();
        $this->assertTrue((bool) $grn->is_invoiced);
        $this->assertNotNull($grn->purchase_invoice_id);
    }

    public function test_anti_double_invoicing_protection_on_goods_receipt(): void
    {
        $po = $this->createPurchaseOrder(4);
        $poItem = $po->items->first();

        // Buat penerimaan instan
        $this->actingAs($this->admin)
            ->post(route('purchase.goods-receipts.store'), [
                'purchase_order_id'     => $po->id,
                'warehouse_id'          => $this->warehouse->id,
                'received_date'         => '2026-09-02',
                'auto_generate_invoice' => '1',
                'items' => [
                    [
                        'purchase_order_item_id' => $poItem->id,
                        'warehouse_id'           => $this->warehouse->id,
                        'qty_physical'           => 4,
                        'qty_received'           => 4,
                        'qty_rejected'           => 0,
                        'condition'              => 'Good',
                        'shortage_reason'        => 'none',
                    ]
                ]
            ]);

        $grn = GoodsReceipt::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($grn);
        $this->submitAndApproveGoodsReceipt($grn);
        $this->assertTrue((bool) $grn->is_invoiced);
        $countBefore = PurchaseInvoice::count();

        // Coba terbitkan invoice ulang untuk GRN yang sama
        $dupResponse = $this->actingAs($this->admin)
            ->post(route('purchase.invoices.store'), [
                'purchase_order_id'       => $po->id,
                'goods_receipt_id'        => $grn->id,
                'supplier_invoice_number' => 'DUP-INV',
                'invoice_date'            => '2026-09-03',
                'due_date'                => '2026-10-03',
                'tax_rate'                => 11,
            ]);

        $dupResponse->assertSessionHas('error');
        $this->assertEquals($countBefore, PurchaseInvoice::count());
    }
}
