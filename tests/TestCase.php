<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function confirmAndApproveSalesOrder(\App\Models\SalesOrder $so, ?\App\Models\User $actor = null): void
    {
        $actor = $actor ?? \App\Models\User::where('role', 'admin')->first() ?? \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($actor)->patch(route('sales.orders.confirm', $so));
        $approval = \App\Models\ApprovalRequest::where('approvable_type', \App\Models\SalesOrder::class)
            ->where('approvable_id', $so->id)
            ->where('status', 'pending')
            ->first();
        if ($approval) {
            $this->actingAs($actor)->patch(route('approvals.approve', $approval));
        }
        $so->refresh();
    }

    protected function submitAndApproveGoodsReceipt(\App\Models\GoodsReceipt $gr, ?\App\Models\User $actor = null): void
    {
        $actor = $actor ?? \App\Models\User::where('role', 'admin')->first() ?? \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($actor)->post(route('purchase.goods-receipts.submit', $gr));
        $approval = \App\Models\ApprovalRequest::where('approvable_type', \App\Models\GoodsReceipt::class)
            ->where('approvable_id', $gr->id)
            ->where('status', 'pending')
            ->first();
        if ($approval) {
            $this->actingAs($actor)->patch(route('approvals.approve', $approval));
        }
        $gr->refresh();
    }
}
