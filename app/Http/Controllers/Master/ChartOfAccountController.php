<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChartOfAccountController extends Controller
{
    public function index(Request $request): View
    {
        $query = ChartOfAccount::query();

        $search = trim($request->input('search') ?: $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $allAccounts = $query->with(['parent', 'children'])->orderBy('code')->get();
        $accounts = $allAccounts->groupBy('type');

        return view('master.chart-of-accounts.index', compact('accounts', 'allAccounts'));
    }

    public function create(): View
    {
        $parentAccounts = ChartOfAccount::orderBy('code')->get();
        return view('master.chart-of-accounts.form', compact('parentAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $existingAcc = $request->filled('code')
            ? ChartOfAccount::where('code', trim($request->code))->first()
            : null;

        $customCodeMsg = $existingAcc
            ? "Kode akun \"{$request->code}\" sudah digunakan oleh akun \"{$existingAcc->name}\"."
            : 'Kode akun ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->validate([
            'parent_id'      => 'nullable|exists:chart_of_accounts,id',
            'code'           => 'required|string|max:50|unique:chart_of_accounts,code',
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:debit,credit',
            'description'    => 'nullable|string',
        ], [
            'code.required'           => 'Kode akun wajib diisi.',
            'code.unique'             => $customCodeMsg,
            'name.required'           => 'Nama akun wajib diisi.',
            'type.required'           => 'Tipe akun wajib dipilih.',
            'type.in'                 => 'Tipe akun yang dipilih tidak valid.',
            'normal_balance.required' => 'Normal balance wajib dipilih.',
            'normal_balance.in'       => 'Normal balance harus debit atau credit.',
        ]);

        ChartOfAccount::create([
            'parent_id'      => $request->filled('parent_id') ? $request->parent_id : null,
            'code'           => strtoupper(trim($request->code)),
            'name'           => trim($request->name),
            'type'           => $request->type,
            'normal_balance' => $request->normal_balance,
            'description'    => $request->description ? trim($request->description) : null,
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.chart-of-accounts.index')
            ->with('success', 'Akun CoA berhasil ditambahkan.');
    }

    public function show(ChartOfAccount $chartOfAccount): View
    {
        $chartOfAccount->load(['parent', 'children', 'journalLines' => fn($q) => $q->with('journalEntry')->latest()->limit(50)]);

        $totalDebit = $chartOfAccount->journalLines()->sum('debit');
        $totalCredit = $chartOfAccount->journalLines()->sum('credit');

        $endingBalance = $chartOfAccount->normal_balance === 'debit'
            ? ($totalDebit - $totalCredit)
            : ($totalCredit - $totalDebit);

        return view('master.chart-of-accounts.show', compact('chartOfAccount', 'totalDebit', 'totalCredit', 'endingBalance'));
    }

    public function edit(ChartOfAccount $chartOfAccount): View
    {
        $parentAccounts = ChartOfAccount::where('id', '!=', $chartOfAccount->id)->orderBy('code')->get();
        return view('master.chart-of-accounts.form', compact('chartOfAccount', 'parentAccounts'));
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $existingAcc = $request->filled('code')
            ? ChartOfAccount::where('code', trim($request->code))->where('id', '!=', $chartOfAccount->id)->first()
            : null;

        $customCodeMsg = $existingAcc
            ? "Kode akun \"{$request->code}\" sudah digunakan oleh akun \"{$existingAcc->name}\"."
            : 'Kode akun ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->validate([
            'parent_id'      => 'nullable|exists:chart_of_accounts,id|not_in:' . $chartOfAccount->id,
            'code'           => 'required|string|max:50|unique:chart_of_accounts,code,' . $chartOfAccount->id,
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:debit,credit',
            'description'    => 'nullable|string',
        ], [
            'code.required'           => 'Kode akun wajib diisi.',
            'code.unique'             => $customCodeMsg,
            'name.required'           => 'Nama akun wajib diisi.',
            'type.required'           => 'Tipe akun wajib dipilih.',
            'type.in'                 => 'Tipe akun yang dipilih tidak valid.',
            'normal_balance.required' => 'Normal balance wajib dipilih.',
            'normal_balance.in'       => 'Normal balance harus debit atau credit.',
        ]);

        $chartOfAccount->update([
            'parent_id'      => $request->filled('parent_id') ? $request->parent_id : null,
            'code'           => strtoupper(trim($request->code)),
            'name'           => trim($request->name),
            'type'           => $request->type,
            'normal_balance' => $request->normal_balance,
            'description'    => $request->description ? trim($request->description) : null,
            'is_active'      => $request->boolean('is_active'),
        ]);

        return redirect()->route('master.chart-of-accounts.index')
            ->with('success', 'Akun CoA berhasil diperbarui.');
    }

    public function toggleStatus(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $chartOfAccount->update([
            'is_active' => !$chartOfAccount->is_active,
        ]);

        $statusLabel = $chartOfAccount->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status akun \"{$chartOfAccount->name}\" berhasil {$statusLabel}.");
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        if ($chartOfAccount->children()->exists()) {
            return back()->with('error', "Akun \"{$chartOfAccount->name}\" ({$chartOfAccount->code}) tidak dapat dihapus karena masih memiliki sub-akun (akun anak). Hapus atau pindahkan sub-akun tersebut terlebih dahulu.");
        }

        if ($chartOfAccount->journalLines()->exists()) {
            return back()->with('error', "Akun \"{$chartOfAccount->name}\" ({$chartOfAccount->code}) tidak dapat dihapus karena sudah memiliki riwayat mutasi dalam jurnal akuntansi. Anda dapat menonaktifkan akun ini sebagai gantinya.");
        }

        try {
            $accName = $chartOfAccount->name;
            $chartOfAccount->delete();
            return redirect()->route('master.chart-of-accounts.index')
                ->with('success', "Akun \"{$accName}\" berhasil dihapus.");
        } catch (\Exception $e) {
            return back()->with('error', "Gagal menghapus akun: " . $e->getMessage());
        }
    }
}
