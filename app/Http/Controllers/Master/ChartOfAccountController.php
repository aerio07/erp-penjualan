<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChartOfAccountController extends Controller
{
    public function index(): View
    {
        $accounts = ChartOfAccount::orderBy('code')->get()->groupBy('type');
        return view('master.chart-of-accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('master.chart-of-accounts.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code'        => 'required|unique:chart_of_accounts,code',
            'name'        => 'required|string',
            'type'        => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:debit,credit',
        ]);

        ChartOfAccount::create($request->only('code', 'name', 'type', 'normal_balance', 'description') + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.chart-of-accounts.index')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function show(ChartOfAccount $chartOfAccount): View
    {
        $chartOfAccount->load(['journalLines' => fn($q) => $q->with('journalEntry')->latest()->limit(20)]);
        return view('master.chart-of-accounts.show', compact('chartOfAccount'));
    }

    public function edit(ChartOfAccount $chartOfAccount): View
    {
        return view('master.chart-of-accounts.form', compact('chartOfAccount'));
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $request->validate([
            'code' => 'required|unique:chart_of_accounts,code,' . $chartOfAccount->id,
            'name' => 'required|string',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:debit,credit',
        ]);

        $chartOfAccount->update($request->only('code', 'name', 'type', 'normal_balance', 'description') + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.chart-of-accounts.index')
            ->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        if ($chartOfAccount->journalLines()->exists()) {
            return back()->with('error', 'Akun tidak dapat dihapus karena sudah digunakan dalam jurnal.');
        }
        $chartOfAccount->delete();
        return redirect()->route('master.chart-of-accounts.index')
            ->with('success', 'Akun berhasil dihapus.');
    }
}
