<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountParentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->finance = User::factory()->create(['role' => 'finance']);
    }

    public function test_can_create_parent_and_child_account(): void
    {
        // 1. Create parent account
        $responseParent = $this->actingAs($this->finance)->post(route('master.chart-of-accounts.store'), [
            'code'           => '1-1100',
            'name'           => 'Kas & Bank',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'parent_id'      => null,
            'is_active'      => 1,
        ]);

        $responseParent->assertRedirect(route('master.chart-of-accounts.index'));

        $parent = ChartOfAccount::where('code', '1-1100')->first();
        $this->assertNotNull($parent);
        $this->assertNull($parent->parent_id);

        // 2. Create child account
        $responseChild = $this->actingAs($this->finance)->post(route('master.chart-of-accounts.store'), [
            'code'           => '1-1110',
            'name'           => 'Bank BCA',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'parent_id'      => $parent->id,
            'is_active'      => 1,
        ]);

        $responseChild->assertRedirect(route('master.chart-of-accounts.index'));

        $child = ChartOfAccount::where('code', '1-1110')->first();
        $this->assertNotNull($child);
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertEquals('Kas & Bank', $child->parent->name);
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_can_update_account_parent(): void
    {
        $parent1 = ChartOfAccount::create([
            'code'           => '1-1100',
            'name'           => 'Kas & Bank',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'is_active'      => true,
        ]);

        $parent2 = ChartOfAccount::create([
            'code'           => '1-1200',
            'name'           => 'Piutang Usaha',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'is_active'      => true,
        ]);

        $child = ChartOfAccount::create([
            'code'           => '1-1199',
            'name'           => 'Akun Uji',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'parent_id'      => $parent1->id,
            'is_active'      => true,
        ]);

        // Update to parent 2
        $response = $this->actingAs($this->finance)->put(route('master.chart-of-accounts.update', $child), [
            'code'           => '1-1199',
            'name'           => 'Akun Uji Diperbarui',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'parent_id'      => $parent2->id,
            'is_active'      => 1,
        ]);

        $response->assertRedirect(route('master.chart-of-accounts.index'));

        $child->refresh();
        $this->assertEquals($parent2->id, $child->parent_id);
    }

    public function test_cannot_set_self_as_parent(): void
    {
        $acc = ChartOfAccount::create([
            'code'           => '1-1100',
            'name'           => 'Kas & Bank',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'is_active'      => true,
        ]);

        $response = $this->actingAs($this->finance)->put(route('master.chart-of-accounts.update', $acc), [
            'code'           => '1-1100',
            'name'           => 'Kas & Bank',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'parent_id'      => $acc->id,
            'is_active'      => 1,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_cannot_delete_parent_account_that_has_children(): void
    {
        $parent = ChartOfAccount::create([
            'code'           => '1-1100',
            'name'           => 'Kas & Bank',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'is_active'      => true,
        ]);

        $child = ChartOfAccount::create([
            'code'           => '1-1110',
            'name'           => 'Bank BCA',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'parent_id'      => $parent->id,
            'is_active'      => true,
        ]);

        $response = $this->actingAs($this->finance)->delete(route('master.chart-of-accounts.destroy', $parent));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('chart_of_accounts', ['id' => $parent->id]);
    }
}
