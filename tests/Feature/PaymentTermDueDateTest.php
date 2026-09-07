<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTermDueDateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $warehouse;
    private Product $product;
    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);
        $this->stockService = app(StockService::class);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-PT',
            'name' => 'Gudang Payment Term',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-PT-1',
            'name' => 'Produk Tes Jatuh Tempo',
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'sell_price' => 20000,
            'is_active' => true,
        ]);
        $this->stockService->recordMovement([
            'product_id'     => $this->product->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'in',
            'quantity'       => 100,
            'unit_cost'      => 10000,
            'movement_date'  => now()->subDays(5)->toDateString(),
            'notes'          => 'Stok awal',
            'user_id'        => $this->admin->id,
        ]);
    }

    public function test_customer_model_computes_payment_term_days(): void
    {
        $c1 = Customer::create(['code' => 'C1', 'name' => 'Net 30 Cust', 'payment_term' => 'NET 30']);
        $c2 = Customer::create(['code' => 'C2', 'name' => 'Net 14 Cust', 'payment_term' => 'NET 14']);
        $c3 = Customer::create(['code' => 'C3', 'name' => 'COD Cust', 'payment_term' => 'COD']);
        $c4 = Customer::create(['code' => 'C4', 'name' => 'Num 45 Cust', 'payment_term' => '45']);

        $this->assertEquals(30, $c1->payment_term_days);
        $this->assertEquals(14, $c2->payment_term_days);
        $this->assertEquals(0, $c3->payment_term_days);
        $this->assertEquals(45, $c4->payment_term_days);
    }

    public function test_supplier_model_computes_payment_term_days(): void
    {
        $s1 = Supplier::create(['code' => 'S1', 'name' => 'Net 60 Supp', 'payment_term' => 'NET 60']);
        $s2 = Supplier::create(['code' => 'S2', 'name' => 'COD Supp', 'payment_term' => 'COD']);

        $this->assertEquals(60, $s1->payment_term_days);
        $this->assertEquals(0, $s2->payment_term_days);
    }

    public function test_confirm_receipt_with_auto_invoice_uses_net_30_days_for_due_date(): void
    {
        $customer = Customer::create([
            'code' => 'C-NET30',
            'name' => 'Customer Net 30',
            'payment_term' => 'NET 30',
            'is_active' => true,
        ]);

        $so = SalesOrder::create([
            'so_number'    => 'SO-PT-001',
            'customer_id'  => $customer->id,
            'user_id'      => $this->admin->id,
            'order_date'   => now()->subDays(2)->toDateString(),
            'status'       => 'confirmed',
            'tax_rate'     => 11,
            'subtotal'     => 200000,
            'tax_amount'   => 22000,
            'total_amount' => 222000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id'     => $this->product->id,
            'qty_ordered'    => 10,
            'unit_price'     => 20000,
            'subtotal'       => 200000,
        ]);

        // Kirim barang (staged / in_transit)
        $deliveryResponse = $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id'   => $this->warehouse->id,
            'delivery_date'  => now()->subDay()->toDateString(),
            'status'         => 'in_transit',
            'items'          => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered'       => 10,
                ],
            ],
        ]);
        $deliveryResponse->assertRedirect(route('sales.deliveries.index'));

        $delivery = Delivery::where('sales_order_id', $so->id)->first();
        $this->assertEquals('in_transit', $delivery->status);

        // Konfirmasi penerimaan barang dengan auto_generate_invoice
        $confirmResponse = $this->actingAs($this->admin)->patch(route('sales.deliveries.confirm-receipt', $delivery), [
            'received_date'         => now()->toDateString(),
            'recipient_name'        => 'Budi Penerima',
            'auto_generate_invoice' => '1',
            'invoice_date'          => now()->toDateString(),
            // due_date intentionally omitted to test backend auto-calculation
        ]);

        $confirmResponse->assertRedirect();
        $delivery->refresh();
        $this->assertTrue((bool)$delivery->is_invoiced);
        $this->assertNotNull($delivery->salesInvoice);

        // Pastikan due_date = invoice_date + 30 days
        $expectedDueDate = now()->addDays(30)->toDateString();
        $this->assertEquals($expectedDueDate, $delivery->salesInvoice->due_date->toDateString());
    }
}
