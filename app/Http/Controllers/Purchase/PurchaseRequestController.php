<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Services\ApprovalService;
use App\Services\StockService;
use App\Traits\ExportsPdfDocument;
use App\Traits\GeneratesDocumentNumber;
use App\Traits\HasListFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    use GeneratesDocumentNumber, HasListFilters, ExportsPdfDocument;

    private const DIVISIONS = [
        'Administrasi & Umum',
        'Purchasing / Pengadaan',
        'Gudang / Inventory',
        'Sales & Marketing',
        'Finance & Accounting',
    ];

    public function __construct(
        private StockService $stockService,
        private ApprovalService $approvalService
    ) {}

    /**
     * Menampilkan daftar Purchase Request.
     */
    public function index(Request $request): View
    {
        $status = $request->get('status', 'all');

        $query = PurchaseRequest::with(['user', 'approver', 'items.product', 'approvalRequests']);

        if ($status !== 'all') {
            if ($status === 'confirmed') {
                $query->whereIn('status', ['confirmed', 'approved']);
            } elseif ($status === 'done') {
                $query->whereIn('status', ['done', 'ordered']);
            } else {
                $query->where('status', $status);
            }
        }

        $query = $this->applySearch($query, $request, ['pr_number', 'division', 'notes', 'user.name']);
        $query = $this->applyDateRange($query, $request, 'request_date');

        $purchaseRequests = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('purchase.requests.index', compact('purchaseRequests', 'status'));
    }

    /**
     * Form pembuatan Purchase Request baru.
     */
    public function create(): View
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'unit', 'min_stock'])
            ->map(function ($p) {
                return [
                    'id'        => $p->id,
                    'sku'       => $p->sku,
                    'name'      => $p->name,
                    'unit'      => $p->unit,
                    'min_stock' => $p->min_stock ?? 0,
                    'on_hand'   => $p->onHandStock(),
                ];
            });

        $divisions = self::DIVISIONS;

        return view('purchase.requests.create', compact('products', 'divisions'));
    }

    /**
     * Menyimpan Purchase Request baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'request_date'     => 'required|date',
            'required_date'    => 'required|date|after_or_equal:request_date',
            'expired_date'     => 'nullable|date|after_or_equal:required_date',
            'division'         => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:1000',
            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.notes'      => 'nullable|string|max:255',
        ], [
            'required_date.after_or_equal' => 'Tanggal dibutuhkan harus sama atau setelah tanggal PR.',
            'items.required'               => 'Tambahkan minimal 1 barang ke dalam daftar.',
            'items.min'                    => 'Tambahkan minimal 1 barang ke dalam daftar.',
            'items.*.qty.min'              => 'Kuantitas barang minimal 1.',
        ]);

        $status = 'draft';

        // Jika admin, izinkan pilih divisi manual; selain admin, kunci sesuai role user yang login
        $division = (auth()->user()->role === 'admin' && $request->filled('division'))
            ? $request->division
            : (auth()->user()->division_name ?? 'Umum');

        $pr = DB::transaction(function () use ($request, $status, $division) {
            $prNumber = $this->generateDocumentNumber(PurchaseRequest::class, 'pr_number', 'PR');

            $pr = PurchaseRequest::create([
                'pr_number'     => $prNumber,
                'user_id'       => auth()->id(),
                'division'      => $division,
                'request_date'  => $request->request_date,
                'required_date' => $request->required_date,
                'expired_date'  => $request->expired_date,
                'status'        => $status,
                'notes'         => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $pr->items()->create([
                    'product_id' => $item['product_id'],
                    'qty'        => $item['qty'],
                    'notes'      => $item['notes'] ?? null,
                ]);
            }

            return $pr;
        });

        return redirect()->route('purchase.requests.index')
            ->with('success', "Draft Purchase Request {$pr->pr_number} berhasil disimpan.");
    }

    /**
     * Menampilkan detail Purchase Request.
     */
    public function show(PurchaseRequest $purchase_request): View
    {
        $purchase_request->load(['user', 'approver', 'items.product', 'approvalRequests.approver']);

        return view('purchase.requests.show', compact('purchase_request'));
    }

    /**
     * Form edit Purchase Request (hanya jika draft, revisi, atau rejected).
     */
    public function edit(PurchaseRequest $purchase_request): View|RedirectResponse
    {
        if (!in_array($purchase_request->status, ['draft', 'revised', 'rejected'])) {
            return redirect()->route('purchase.requests.show', $purchase_request)
                ->with('error', 'Purchase Request yang sudah diajukan atau disetujui tidak dapat diedit.');
        }

        $purchase_request->load(['items.product', 'approvalRequests.approver']);

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'unit', 'min_stock'])
            ->map(function ($p) {
                return [
                    'id'        => $p->id,
                    'sku'       => $p->sku,
                    'name'      => $p->name,
                    'unit'      => $p->unit,
                    'min_stock' => $p->min_stock ?? 0,
                    'on_hand'   => $p->onHandStock(),
                ];
            });

        $divisions = self::DIVISIONS;

        return view('purchase.requests.edit', compact('purchase_request', 'products', 'divisions'));
    }

    /**
     * Memperbarui Purchase Request.
     */
    public function update(Request $request, PurchaseRequest $purchase_request): RedirectResponse
    {
        if (!in_array($purchase_request->status, ['draft', 'revised', 'rejected'])) {
            return redirect()->route('purchase.requests.show', $purchase_request)
                ->with('error', 'Purchase Request tidak dapat diedit pada status ini.');
        }

        $request->validate([
            'request_date'     => 'required|date',
            'required_date'    => 'required|date|after_or_equal:request_date',
            'expired_date'     => 'nullable|date|after_or_equal:required_date',
            'division'         => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:1000',
            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.notes'      => 'nullable|string|max:255',
        ]);

        // Jika admin, izinkan update divisi; selain admin, pertahankan divisi dokumen / role user
        $division = (auth()->user()->role === 'admin' && $request->filled('division'))
            ? $request->division
            : ($purchase_request->division ?? auth()->user()->division_name ?? 'Umum');

        DB::transaction(function () use ($request, $purchase_request, $division) {
            $purchase_request->update([
                'division'      => $division,
                'request_date'  => $request->request_date,
                'required_date' => $request->required_date,
                'expired_date'  => $request->expired_date,
                'notes'         => $request->notes,
                'rejection_reason' => null,
            ]);

            $purchase_request->items()->delete();

            foreach ($request->items as $item) {
                $purchase_request->items()->create([
                    'product_id' => $item['product_id'],
                    'qty'        => $item['qty'],
                    'notes'      => $item['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('purchase.requests.show', $purchase_request)
            ->with('success', 'Purchase Request berhasil diperbarui.');
    }

    /**
     * Mengajukan draft/revisi Purchase Request untuk persetujuan (approval).
     */
    public function submit(PurchaseRequest $purchase_request): RedirectResponse
    {
        if (!in_array($purchase_request->status, ['draft', 'revised', 'rejected'])) {
            return back()->with('error', 'Hanya dokumen berstatus Draft, Revisi, atau Ditolak yang dapat diajukan untuk approval.');
        }

        $purchase_request->load(['items.product', 'user']);

        $purchase_request->update([
            'status'           => 'waiting_approval',
            'rejection_reason' => null,
        ]);

        $this->approvalService->request(
            PurchaseRequest::class,
            $purchase_request->id,
            0,
            "PR #{$purchase_request->pr_number} diajukan oleh " . ($purchase_request->user->name ?? 'User') . " (" . ($purchase_request->division ?? 'Umum') . ")."
        );

        return redirect()->route('purchase.requests.index')
            ->with('success', "Purchase Request {$purchase_request->pr_number} berhasil diajukan. Dokumen kini berada di antrean Menu Approval.");
    }

    /**
     * Menghapus Purchase Request (hanya draft atau revisi).
     */
    public function destroy(PurchaseRequest $purchase_request): RedirectResponse
    {
        if (!in_array($purchase_request->status, ['draft', 'revised'])) {
            return back()->with('error', 'Hanya Purchase Request berstatus Draft atau Revisi yang dapat dihapus.');
        }

        $purchase_request->delete();

        return redirect()->route('purchase.requests.index')->with('success', 'Draft Purchase Request berhasil dihapus.');
    }

    /**
     * Approve Purchase Request.
     */
    public function approve(PurchaseRequest $purchase_request): RedirectResponse
    {
        if ($purchase_request->status !== 'waiting_approval') {
            return back()->with('error', 'Hanya PR yang sedang menunggu approval yang dapat disetujui.');
        }

        $approval = $purchase_request->approvalRequests()->where('status', 'pending')->latest()->first();
        if ($approval) {
            $this->approvalService->approve($approval);
        }

        $purchase_request->update([
            'status'      => 'confirmed',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', "Purchase Request {$purchase_request->pr_number} berhasil disetujui.");
    }

    /**
     * Kembalikan Purchase Request untuk revisi.
     */
    public function revise(Request $request, PurchaseRequest $purchase_request): RedirectResponse
    {
        if ($purchase_request->status !== 'waiting_approval') {
            return back()->with('error', 'Hanya PR yang sedang menunggu approval yang dapat diminta revisi.');
        }

        $request->validate([
            'revision_reason' => 'required|string|max:500',
        ]);

        $approval = $purchase_request->approvalRequests()->where('status', 'pending')->latest()->first();
        if ($approval) {
            $this->approvalService->revise($approval, $request->revision_reason);
        }

        $purchase_request->update([
            'status'           => 'revised',
            'rejection_reason' => $request->revision_reason,
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
        ]);

        return back()->with('success', "Purchase Request {$purchase_request->pr_number} telah dikembalikan untuk revisi.");
    }

    /**
     * Reject Purchase Request.
     */
    public function reject(Request $request, PurchaseRequest $purchase_request): RedirectResponse
    {
        if ($purchase_request->status !== 'waiting_approval') {
            return back()->with('error', 'Hanya PR yang sedang menunggu approval yang dapat ditolak.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $approval = $purchase_request->approvalRequests()->where('status', 'pending')->latest()->first();
        if ($approval) {
            $this->approvalService->reject($approval, $request->rejection_reason);
        }

        $purchase_request->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
        ]);

        return back()->with('success', "Purchase Request {$purchase_request->pr_number} telah ditolak.");
    }

    /**
     * Endpoint API JSON untuk mengambil daftar item PR saat dipilih di form PO.
     */
    public function apiGetItems(PurchaseRequest $purchase_request): JsonResponse
    {
        $purchase_request->load(['items.product', 'warehouse']);

        $items = $purchase_request->items->map(function ($item) {
            return [
                'id'            => $item->id,
                'product_id'    => $item->product_id,
                'product_sku'   => $item->product->sku,
                'product_name'  => $item->product->name,
                'unit'          => $item->product->unit,
                'purchase_price' => (float) $item->product->purchase_price,
                'qty'           => $item->qty_remaining,
                'notes'         => $item->notes,
            ];
        });

        return response()->json([
            'success'       => true,
            'pr_number'     => $purchase_request->pr_number,
            'warehouse_id'  => $purchase_request->warehouse_id,
            'required_date' => $purchase_request->required_date?->format('Y-m-d'),
            'notes'         => $purchase_request->notes,
            'items'         => $items,
        ]);
    }

    /**
     * Ekspor Purchase Request ke format PDF.
     */
    public function exportPdf(PurchaseRequest $purchase_request)
    {
        abort_if(!in_array($purchase_request->status, ['confirmed', 'done', 'approved', 'ordered']), 403, 'PDF hanya dapat diunduh setelah Purchase Request disetujui atau diproses ke PO.');

        $purchase_request->load([
            'user',
            'approver',
            'category',
            'warehouse',
            'purchaseOrders',
            'items.product',
        ]);

        $filename = 'Purchase-Request-' . $purchase_request->pr_number;

        return $this->streamPdf('pdf.purchase-request', compact('purchase_request'), $filename);
    }
}