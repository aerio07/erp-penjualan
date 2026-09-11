<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FixedAssetController extends Controller
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Tampilkan daftar dan ringkasan fixed asset
     */
    public function index(Request $request): View
    {
        $categories = FixedAssetCategory::where('is_active', true)->orderBy('name')->get();

        // Query Dasar
        $query = FixedAsset::with(['category.account', 'creator', 'journalEntry']);

        // Filter Pencarian
        $search = trim($request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('acquisition_type')) {
            $query->where('acquisition_type', $request->acquisition_type);
        }

        // Summary KPI
        $summary = [
            'total_units'  => FixedAsset::count(),
            'total_value'  => (float) FixedAsset::sum('purchase_cost'),
            'active_units' => FixedAsset::where('status', 'active')->count(),
            'by_category'  => FixedAssetCategory::withCount('fixedAssets')
                ->withSum('fixedAssets', 'purchase_cost')
                ->get(),
        ];

        $perPage = $request->integer('per_page', 15);
        $assets = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        return view('accounting.fixed-assets.index', compact('assets', 'categories', 'summary'));
    }

    /**
     * Form pendaftaran aset baru
     */
    public function create(): View
    {
        $categories = FixedAssetCategory::where('is_active', true)->with('account')->orderBy('name')->get();
        // Akun Kas & Bank untuk pembayaran tunai
        $paymentAccounts = ChartOfAccount::where('type', 'asset')
            ->where(function ($q) {
                $q->where('code', 'like', '1-11%')
                  ->orWhere('name', 'like', '%Kas%')
                  ->orWhere('name', 'like', '%Bank%');
            })
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.fixed-assets.create', compact('categories', 'paymentAccounts'));
    }

    /**
     * Simpan pendaftaran aset (Mendukung batch unit detail & auto-journal kas)
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'category_id'        => 'required|exists:fixed_asset_categories,id',
            'name'               => 'required|string|max:255',
            'acquisition_type'   => 'required|in:purchase,opening_balance',
            'purchase_date'      => 'required|date',
            'unit_cost'          => 'required|numeric|min:0',
            'quantity'           => 'required|integer|min:1|max:100',
            'payment_account_id' => 'required_if:acquisition_type,purchase|nullable|exists:chart_of_accounts,id',
            'location'           => 'nullable|string|max:100',
            'useful_life_months' => 'nullable|integer|min:0',
            'salvage_value'      => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string',
            'serial_numbers'     => 'nullable|array',
        ]);

        $category = FixedAssetCategory::findOrFail($request->category_id);
        $quantity = (int) $request->quantity;
        $unitCost = (float) $request->unit_cost;
        $totalCost = round($unitCost * $quantity, 2);
        $batchRef = 'AST-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        try {
            DB::transaction(function () use ($request, $category, $quantity, $unitCost, $totalCost, $batchRef) {
                $journalEntryId = null;

                // 1. Jika Pembelian Baru (purchase): Buat Jurnal Kas Keluar
                if ($request->acquisition_type === 'purchase') {
                    $journal = $this->journalService->createFromFixedAssetPurchase(
                        category: $category,
                        totalCost: $totalCost,
                        paymentAccountId: (int) $request->payment_account_id,
                        date: $request->purchase_date,
                        description: "Pembelian {$quantity} unit {$request->name} ({$category->name})",
                        batchRef: $batchRef
                    );
                    $journalEntryId = $journal->id;
                }
                // Jika Saldo Awal (opening_balance): Tanpa jurnal kas baru! Nilai sudah ada di saldo awal neraca.

                // 2. Generate Nomor Urut Aset (Atomic & Race-Condition Safe)
                $prefix = 'AST-' . strtoupper($category->code) . '-';
                
                // Cari angka urutan tertinggi untuk prefix kategori ini dengan lockForUpdate
                $lastAsset = FixedAsset::where('asset_code', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                $lastNumber = 0;
                if ($lastAsset && preg_match('/(\d+)$/', $lastAsset->asset_code, $matches)) {
                    $lastNumber = (int) $matches[1];
                }

                $serialNumbers = $request->input('serial_numbers', []);

                // 3. Simpan unit detail sebanyak quantity (N baris record)
                for ($i = 1; $i <= $quantity; $i++) {
                    $seqNumber = $lastNumber + $i;
                    $assetCode = $prefix . str_pad($seqNumber, 5, '0', STR_PAD_LEFT);
                    $sn = isset($serialNumbers[$i - 1]) ? trim($serialNumbers[$i - 1]) : null;

                    FixedAsset::create([
                        'category_id'        => $category->id,
                        'asset_code'         => $assetCode,
                        'name'               => $request->name,
                        'acquisition_type'   => $request->acquisition_type,
                        'purchase_date'      => $request->purchase_date,
                        'purchase_cost'      => $unitCost,
                        'salvage_value'      => (float) ($request->salvage_value ?? 0),
                        'useful_life_months' => (int) ($request->useful_life_months ?: $category->useful_life_months),
                        'depreciation_method'=> 'straight_line',
                        'status'             => 'active',
                        'location'           => $request->location,
                        'serial_number'      => $sn,
                        'notes'              => $request->notes,
                        'journal_entry_id'   => $journalEntryId,
                        'batch_reference'    => $batchRef,
                        'created_by'         => Auth::id() ?? 1,
                    ]);
                }
            });

            $typeMsg = $request->acquisition_type === 'purchase'
                ? "Pembelian {$quantity} unit aset berhasil dicatat & jurnal kas otomatis terposting!"
                : "Pendaftaran {$quantity} unit aset (Saldo Awal) berhasil dicatat tanpa mutasi kas!";

            return redirect()->route('accounting.fixed-assets.index')->with('success', $typeMsg);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal mendaftarkan aset: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail aset
     */
    public function show(FixedAsset $fixedAsset): View
    {
        $fixedAsset->load(['category.account', 'journalEntry.lines.chartOfAccount', 'creator']);

        return view('accounting.fixed-assets.show', compact('fixedAsset'));
    }

    /**
     * Update data non-finansial aset (lokasi, status, notes, serial_number)
     */
    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $request->validate([
            'status'        => 'required|in:active,maintenance,disposed,sold',
            'location'      => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
        ]);

        $fixedAsset->update($request->only(['status', 'location', 'serial_number', 'notes']));

        return redirect()->route('accounting.fixed-assets.show', $fixedAsset)
            ->with('success', "Informasi aset #{$fixedAsset->asset_code} berhasil diperbarui.");
    }
}
