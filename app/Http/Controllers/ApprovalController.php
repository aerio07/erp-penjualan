<?php

namespace App\Http\Controllers;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\SalesOrderStatus;
use App\Models\ApprovalRequest;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SalesOrder;
use App\Http\Controllers\Purchase\GoodsReceiptController;
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

        $perPage = (int) $request->get('per_page', 10);
        $pendingApprovals = (clone $query)->where('status', 'pending')->paginate($perPage, ['*'], 'pending_page')->withQueryString();

        $myApprovals = ApprovalRequest::with(['requester', 'approvable', 'approver'])
            ->whereIn('status', ['approved', 'rejected', 'revised'])
            ->orderByRaw('COALESCE(approved_at, updated_at) DESC')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        return view('approvals.index', compact('pendingApprovals', 'myApprovals'));
    }

    public function approve(ApprovalRequest $approval): RedirectResponse
    {
        $this->approvalService->approve($approval);

        // Update status dokumen terkait
        $approvable = $approval->approvable;
        if ($approvable && method_exists($approvable, 'update')) {
            if ($approvable instanceof GoodsReceipt) {
                app(GoodsReceiptController::class)->processApproval($approvable, auth()->id());
            } elseif ($approvable instanceof PurchaseRequest) {
                $approvable->update([
                    'status'      => PurchaseRequestStatus::Confirmed,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            } else {
                $status = ($approvable instanceof SalesOrder) ? SalesOrderStatus::Confirmed : PurchaseOrderStatus::Confirmed;
                $approvable->update(['status' => $status]);

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
            if ($approvable instanceof GoodsReceipt) {
                $approvable->update([
                    'status'           => GoodsReceiptStatus::Rejected,
                    'rejection_reason' => $request->rejection_reason,
                    'approved_by'      => auth()->id(),
                    'approved_at'      => now(),
                ]);
            } elseif ($approvable instanceof PurchaseRequest) {
                $approvable->update([
                    'status'           => PurchaseRequestStatus::Rejected,
                    'rejection_reason' => $request->rejection_reason,
                    'approved_by'      => auth()->id(),
                    'approved_at'      => now(),
                ]);
            } else {
                $status = ($approvable instanceof SalesOrder) ? SalesOrderStatus::Cancelled : PurchaseOrderStatus::Cancelled;
                $approvable->update(['status' => $status]);

                if ($approvable instanceof SalesOrder) {
                    $this->stockService->releaseReservationsForSalesOrder($approvable);
                } elseif ($approvable instanceof PurchaseOrder) {
                    $approvable->procurementDemands()->update([
                        'purchase_order_id' => null,
                        'status'            => 'pending',
                    ]);
                    $approvable->reopenPurchaseRequest();
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

        // Ubah status dokumen terkait untuk direvisi
        $approvable = $approval->approvable;
        if ($approvable && method_exists($approvable, 'update')) {
            if ($approvable instanceof GoodsReceipt) {
                $approvable->update([
                    'status'           => GoodsReceiptStatus::Revised,
                    'rejection_reason' => $request->revision_reason,
                ]);
            } elseif ($approvable instanceof PurchaseRequest) {
                $approvable->update([
                    'status'           => PurchaseRequestStatus::Revised,
                    'rejection_reason' => $request->revision_reason,
                ]);
            } elseif ($approvable instanceof PurchaseOrder) {
                $approvable->update([
                    'status'        => PurchaseOrderStatus::Draft,
                    'cancel_reason' => $request->revision_reason,
                ]);
                $approvable->procurementDemands()->update([
                    'purchase_order_id' => null,
                    'status'            => 'pending',
                ]);
            } else {
                $approvable->update([
                    'status'             => SalesOrderStatus::Draft,
                    'fulfillment_status' => 'pending',
                ]);

                if ($approvable instanceof SalesOrder) {
                    $this->stockService->releaseReservationsForSalesOrder($approvable);
                }
            }
        }

        return back()->with('success', "Approval #{$approval->request_number} dikembalikan untuk revisi.");
    }
}
