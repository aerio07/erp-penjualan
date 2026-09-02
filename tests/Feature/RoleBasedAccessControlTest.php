<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function admin_has_full_access_including_approvals()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Can access approvals
        $response = $this->actingAs($admin)->get(route('approvals.index'));
        $response->assertOk();

        // Sidebar contains Approval and Akuntansi
        $dashResponse = $this->actingAs($admin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Approval');
        $dashResponse->assertSee('Akuntansi');
        $dashResponse->assertSee('User Management');
    }

    #[Test]
    public function finance_cannot_access_approvals_and_sees_only_finance_menus()
    {
        $finance = User::factory()->create(['role' => 'finance']);

        // Approvals is forbidden (403)
        $response = $this->actingAs($finance)->get(route('approvals.index'));
        $response->assertForbidden();

        // Warehouse operations are forbidden
        $this->actingAs($finance)->get(route('inventory.transfers.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('inventory.opname.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('sales.deliveries.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('purchase.goods-receipts.index'))->assertForbidden();

        // Can access finance & accounting
        $this->actingAs($finance)->get(route('accounting.journals.index'))->assertOk();
        $this->actingAs($finance)->get(route('sales.invoices.index'))->assertOk();
        $this->actingAs($finance)->get(route('purchase.invoices.index'))->assertOk();

        // Sidebar verification
        $dashResponse = $this->actingAs($finance)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Akuntansi');
        $dashResponse->assertSee('Invoice Pembelian');
        $dashResponse->assertSee('Invoice Penjualan');
        $dashResponse->assertDontSee('User Management');
    }

    #[Test]
    public function gudang_cannot_access_approvals_or_accounting_and_sees_only_warehouse_menus()
    {
        $gudang = User::factory()->create(['role' => 'gudang']);

        // Approvals and Accounting are forbidden
        $this->actingAs($gudang)->get(route('approvals.index'))->assertForbidden();
        $this->actingAs($gudang)->get(route('accounting.journals.index'))->assertForbidden();
        $this->actingAs($gudang)->get(route('accounting.reports.ledger'))->assertForbidden();
        $this->actingAs($gudang)->get(route('sales.invoices.index'))->assertForbidden();
        $this->actingAs($gudang)->get(route('purchase.invoices.index'))->assertForbidden();

        // Can access warehouse operations
        $this->actingAs($gudang)->get(route('inventory.stock-summary'))->assertOk();
        $this->actingAs($gudang)->get(route('inventory.movements.index'))->assertOk();
        $this->actingAs($gudang)->get(route('sales.deliveries.index'))->assertOk();
        $this->actingAs($gudang)->get(route('purchase.goods-receipts.index'))->assertOk();

        // Sidebar verification
        $dashResponse = $this->actingAs($gudang)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Penerimaan Barang');
        $dashResponse->assertSee('Surat Jalan');
        $dashResponse->assertSee('Mutasi Stok');
        $dashResponse->assertDontSee('Akuntansi');
        $dashResponse->assertDontSee('User Management');
    }

    #[Test]
    public function sales_cannot_access_approvals_purchasing_or_accounting()
    {
        $sales = User::factory()->create(['role' => 'sales']);

        // Forbidden areas
        $this->actingAs($sales)->get(route('approvals.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('accounting.journals.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('purchase.orders.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('inventory.transfers.index'))->assertForbidden();

        // Can access sales orders
        $this->actingAs($sales)->get(route('sales.orders.index'))->assertOk();

        // Sidebar verification
        $dashResponse = $this->actingAs($sales)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Sales Order');
        $dashResponse->assertDontSee('Purchase Order');
        $dashResponse->assertDontSee('Akuntansi');
    }

    #[Test]
    public function only_admin_sees_approval_alert_when_po_waiting_approval_exists()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance']);
        $gudang = User::factory()->create(['role' => 'gudang']);

        $supplier = \App\Models\Supplier::first() ?? \App\Models\Supplier::create([
            'name' => 'Supplier Test',
            'code' => 'SUP-TEST-' . uniqid(),
            'contact_person' => 'Budi',
            'phone' => '08123456789',
            'address' => 'Jakarta',
            'is_active' => true,
        ]);
        $warehouse = \App\Models\Warehouse::first() ?? \App\Models\Warehouse::create([
            'name' => 'Gudang Test',
            'code' => 'GDG-' . uniqid(),
            'address' => 'Jakarta',
            'is_active' => true,
        ]);

        // Create PO waiting approval
        PurchaseOrder::create([
            'po_number' => 'PO-TEST-' . uniqid(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
            'order_date' => now()->toDateString(),
            'status' => 'waiting_approval',
            'total_amount' => 100_000_000,
        ]);

        // Admin sees approval alert
        $adminResp = $this->actingAs($admin)->get(route('dashboard'));
        $adminResp->assertSee('menunggu approval limit');

        // Finance does NOT see approval alert
        $finResp = $this->actingAs($finance)->get(route('dashboard'));
        $finResp->assertDontSee('menunggu approval limit');

        // Gudang does NOT see approval alert
        $gudangResp = $this->actingAs($gudang)->get(route('dashboard'));
        $gudangResp->assertDontSee('menunggu approval limit');
    }
}
