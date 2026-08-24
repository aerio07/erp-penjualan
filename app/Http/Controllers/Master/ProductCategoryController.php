<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Traits\HasListFilters;

class ProductCategoryController extends Controller
{
    use HasListFilters;

    public function index(Request $request): View
    {
        $query = ProductCategory::withCount('products');

        $query = $this->applySearch($query, $request, ['code', 'name', 'description']);
        $query = $this->applyFilter($query, $request, 'is_active');
        $query = $this->applySort($query, $request, ['code', 'name', 'products_count', 'created_at'], 'name', 'asc');

        $perPage = (int) $request->get('per_page', 20);
        $categories = $query->paginate($perPage)->withQueryString();

        return view('master.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('master.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $existingCat = $request->filled('code') 
            ? ProductCategory::where('code', trim($request->code))->first() 
            : null;

        $customCodeMsg = $existingCat 
            ? "Kode kategori \"{$request->code}\" sudah digunakan oleh kategori \"{$existingCat->name}\"." 
            : 'Kode kategori ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->validate([
            'code'        => 'required|string|max:50|unique:product_categories,code',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'code.required' => 'Kode kategori wajib diisi.',
            'code.unique'   => $customCodeMsg,
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        ProductCategory::create([
            'code'        => strtoupper(trim($request->code)),
            'name'        => trim($request->name),
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.categories.index')
            ->with('success', "Kategori produk \"{$request->name}\" berhasil ditambahkan.");
    }

    public function show(ProductCategory $category): View
    {
        $category->load(['products' => function ($q) {
            $q->orderBy('name');
        }]);

        $totalProducts = $category->products->count();
        $totalStock    = $category->products->sum(fn($p) => $p->onHandStock());

        return view('master.categories.show', compact('category', 'totalProducts', 'totalStock'));
    }

    public function edit(ProductCategory $category): View
    {
        return view('master.categories.create', compact('category'));
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        $existingCat = $request->filled('code') 
            ? ProductCategory::where('code', trim($request->code))->where('id', '!=', $category->id)->first() 
            : null;

        $customCodeMsg = $existingCat 
            ? "Kode kategori \"{$request->code}\" sudah digunakan oleh kategori \"{$existingCat->name}\"." 
            : 'Kode kategori ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->validate([
            'code'        => 'required|string|max:50|unique:product_categories,code,' . $category->id,
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'code.required' => 'Kode kategori wajib diisi.',
            'code.unique'   => $customCodeMsg,
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        $newName = trim($request->name);

        $category->update([
            'code'        => strtoupper(trim($request->code)),
            'name'        => $newName,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active'),
        ]);

        // Sinkronisasi string category pada produk terkait jika nama kategori diubah
        Product::where('category_id', $category->id)->update(['category' => $newName]);

        return redirect()->route('master.categories.index')
            ->with('success', "Kategori produk \"{$newName}\" berhasil diperbarui.");
    }

    public function toggleStatus(ProductCategory $category): RedirectResponse
    {
        $category->update([
            'is_active' => !$category->is_active,
        ]);

        $statusLabel = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status kategori \"{$category->name}\" berhasil {$statusLabel}.");
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        $productsCount = $category->products()->count();
        if ($productsCount > 0) {
            return back()->with('error', "Kategori \"{$category->name}\" tidak dapat dihapus karena masih digunakan oleh {$productsCount} produk. Anda dapat menonaktifkan kategori ini sebagai gantinya.");
        }

        try {
            $catName = $category->name;
            $category->delete();
            return redirect()->route('master.categories.index')
                ->with('success', "Kategori \"{$catName}\" berhasil dihapus.");
        } catch (\Exception $e) {
            return back()->with('error', "Gagal menghapus kategori: " . $e->getMessage());
        }
    }
}
