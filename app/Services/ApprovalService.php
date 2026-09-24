<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Support\Facades\Auth;

class ApprovalService
{
    use GeneratesDocumentNumber;
    /** Threshold nilai SO yang butuh approval (dalam Rupiah) */
    const SO_APPROVAL_THRESHOLD = 50_000_000;

    /**
     * Buat approval request untuk sebuah dokumen.
     */
    public function request(string $type, int $id, float $amount, string $notes = ''): ApprovalRequest
    {
        $existing = ApprovalRequest::where('approvable_type', $type)
            ->where('approvable_id', $id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            $existing->update([
                'amount' => $amount,
                'notes'  => $notes,
            ]);
            return $existing;
        }

        return ApprovalRequest::create([
            'approvable_type' => $type,
            'approvable_id'   => $id,
            'status'          => 'pending',
            'request_number'  => $this->generateNumber(),
            'requester_id'    => Auth::id(),
            'amount'          => $amount,
            'notes'           => $notes,
        ]);
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
    public function reject(ApprovalRequest $approval, ?string $reason = null): ApprovalRequest
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
     * Kembalikan approval request untuk revisi.
     */
    public function revise(ApprovalRequest $approval, ?string $reason = null): ApprovalRequest
    {
        $approval->update([
            'status'           => 'revised',
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
            'purchase_request' => true,
            'purchase_order'   => true,
            'sales_order'      => true,
            'goods_receipt'    => true,
            default            => false,
        };
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(ApprovalRequest::class, 'request_number', 'APR');
    }
}
