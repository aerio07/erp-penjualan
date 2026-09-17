<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SalesOrder;
use App\Services\ApprovalService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class ApprovalController extends Controller
{
    use HasListFilters;

    public function __construct(
        private ApprovalService $approvalService,
        private StockService $stockService
    ) {}

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
            if ($approvable instanceof PurchaseRequest) {
                $approvable->update([
                    'status'      => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            } else {
                $approvable->update(['status' => 'confirmed']);

                if ($approvable instanceof SalesOrder) {
                    $this->stockService->allocateStockForSalesOrder($approvable);
                }
            }
        }

        return back()->with('success', "Approval #{$approval->request_number} berhasil disetujui.");
    }

    public function reject(Request $request, ApprovalRequest $approval): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'nullable|string']);

        $this->approvalService->reject($approval, $request->rejection_reason);

        // Ubah status dokumen terkait menjadi cancelled / rejected
        $approvable = $approval->approvable;
        if ($approvable && method_exists($approvable, 'update')) {
            if ($approvable instanceof PurchaseRequest) {
                $approvable->update([
                    'status'           => 'rejected',
                    'rejection_reason' => $request->rejection_reason,
                    'approved_by'      => auth()->id(),
                    'approved_at'      => now(),
                ]);
            } else {
                $approvable->update(['status' => 'cancelled']);

                if ($approvable instanceof SalesOrder) {
                    $this->stockService->releaseReservationsForSalesOrder($approvable);
                } elseif ($approvable instanceof PurchaseOrder) {
                    $approvable->procurementDemands()->update([
                        'purchase_order_id' => null,
                        'status'            => 'pending',
                    ]);
                }
            }
        }

        return back()->with('success', "Approval #{$approval->request_number} berhasil ditolak.");
    }

    public function revise(Request $request, ApprovalRequest $approval): RedirectResponse
    {
        $request->validate(['revision_reason' => 'required|string|min:3'], [
            'revision_reason.required' => 'Alasan revisi wajib diisi!',
            'revision_reason.min'      => 'Alasan revisi minimal 3 karakter!',
        ]);

        $this->approvalService->revise($approval, $request->revision_reason);

        // Ubah status dokumen terkait kembali ke draft untuk direvisi
        $approvable = $approval->approvable;
        if ($approvable && method_exists($approvable, 'update')) {
            if ($approvable instanceof PurchaseRequest) {
                $approvable->update([
                    'status'           => 'draft',
                    'rejection_reason' => $request->revision_reason,
                ]);
            } else {
                $approvable->update([
                    'status'             => 'draft',
                    'fulfillment_status' => 'pending',
                ]);

                if ($approvable instanceof SalesOrder) {
                    $this->stockService->releaseReservationsForSalesOrder($approvable);
                } elseif ($approvable instanceof PurchaseOrder) {
                    $approvable->procurementDemands()->update([
                        'purchase_order_id' => null,
                        'status'            => 'pending',
                    ]);
                }
            }
        }

        return back()->with('success', "Approval #{$approval->request_number} dikembalikan untuk revisi.");
    }
}
