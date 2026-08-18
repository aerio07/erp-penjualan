<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use Illuminate\Support\Facades\Auth;

class ApprovalService
{
    /** Threshold nilai SO yang butuh approval (dalam Rupiah) */
    const SO_APPROVAL_THRESHOLD = 50_000_000;

    /**
     * Buat approval request untuk sebuah dokumen.
     */
    public function request(string $type, int $id, float $amount, string $notes = ''): ApprovalRequest
    {
        return ApprovalRequest::firstOrCreate(
            [
                'approvable_type' => $type,
                'approvable_id'   => $id,
                'status'          => 'pending',
            ],
            [
                'request_number'  => $this->generateNumber(),
                'requester_id'    => Auth::id(),
                'amount'          => $amount,
                'notes'           => $notes,
            ]
        );
    }

    /**
     * Setujui approval request.
     */
    public function approve(ApprovalRequest $approval): ApprovalRequest
    {
        $approval->update([
            'status'      => 'approved',
            'approver_id' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $approval->fresh();
    }

    /**
     * Tolak approval request.
     */
    public function reject(ApprovalRequest $approval, string $reason): ApprovalRequest
    {
        $approval->update([
            'status'           => 'rejected',
            'approver_id'      => Auth::id(),
            'rejection_reason' => $reason,
            'approved_at'      => now(),
        ]);

        return $approval->fresh();
    }

    /**
     * Cek apakah amount melewati threshold dan butuh approval.
     */
    public function needsApproval(string $docType, float $amount): bool
    {
        return match ($docType) {
            'purchase_order' => true,
            'sales_order'    => $amount >= self::SO_APPROVAL_THRESHOLD,
            default          => false,
        };
    }

    private function generateNumber(): string
    {
        $prefix = 'APR-' . date('Ym') . '-';
        $last   = ApprovalRequest::where('request_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('request_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
