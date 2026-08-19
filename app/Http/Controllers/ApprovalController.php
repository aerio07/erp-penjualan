<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class ApprovalController extends Controller
{
    use HasListFilters;

    public function __construct(private ApprovalService $approvalService) {}

    public function index(Request $request): View
    {
        $query = ApprovalRequest::with(['requester', 'approvable']);

        $query = $this->applySearch($query, $request, ['request_number', 'notes', 'rejection_reason']);
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'approvable_type');
        $query = $this->applyDateRange($query, $request, 'created_at');
        $query = $this->applySort($query, $request, ['request_number', 'amount', 'status', 'created_at'], 'created_at', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $pendingApprovals = (clone $query)->where('status', 'pending')->paginate($perPage, ['*'], 'pending_page')->withQueryString();

        $myApprovals = ApprovalRequest::with(['requester', 'approvable'])
            ->where('approver_id', auth()->id())
            ->latest('created_at')
            ->paginate(10, ['*'], 'my_page')
            ->withQueryString();

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
