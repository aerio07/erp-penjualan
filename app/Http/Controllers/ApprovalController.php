<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    public function index(): View
    {
        $pendingApprovals = ApprovalRequest::with(['requester', 'approvable'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        $myApprovals = ApprovalRequest::with(['requester', 'approvable'])
            ->where('approver_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('approvals.index', compact('pendingApprovals', 'myApprovals'));
    }

    public function approve(ApprovalRequest $approval): RedirectResponse
    {
        $this->approvalService->approve($approval);

        // Update status dokumen terkait
        $approvable = $approval->approvable;
        if ($approvable && method_exists($approvable, 'update')) {
            $approvable->update(['status' => 'confirmed']);
        }

        return back()->with('success', "Approval #{$approval->request_number} berhasil disetujui.");
    }

    public function reject(Request $request, ApprovalRequest $approval): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|min:3']);

        $this->approvalService->reject($approval, $request->rejection_reason);

        // Kembalikan dokumen ke draft
        $approvable = $approval->approvable;
        if ($approvable && method_exists($approvable, 'update')) {
            $approvable->update(['status' => 'draft']);
        }

        return back()->with('success', "Approval #{$approval->request_number} ditolak.");
    }
}
