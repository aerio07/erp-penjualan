<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::orderBy('name')->paginate(20);
        return view('master.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('master.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sku'            => 'required|unique:products,sku',
            'name'           => 'required|string',
            'unit'           => 'required|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'sell_price'     => 'nullable|numeric|min:0',
            'min_stock'      => 'nullable|integer|min:0',
        ]);

        Product::create([
            'sku'            => $request->sku,
            'name'           => $request->name,
            'category'       => $request->category,
            'unit'           => $request->unit,
            'purchase_price' => $request->purchase_price ?? 0,
            'sell_price'     => $request->sell_price ?? 0,
            'min_stock'      => $request->min_stock ?? 0,
            'is_active'      => $request->boolean('is_active', true),
            'notes'          => $request->notes,
        ]);

        return redirect()->route('master.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): View
    {
        return view('master.products.create', compact('product'));
    }

    public function edit(Product $product): View
    {
        return view('master.products.create', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'sku'  => 'required|unique:products,sku,' . $product->id,
            'name' => 'required|string',
            'unit' => 'required|string',
        ]);

        $product->update([
            'sku'            => $request->sku,
            'name'           => $request->name,
            'category'       => $request->category,
            'unit'           => $request->unit,
            'purchase_price' => $request->purchase_price ?? 0,
            'sell_price'     => $request->sell_price ?? 0,
            'min_stock'      => $request->min_stock ?? 0,
            'is_active'      => $request->boolean('is_active', true),
            'notes'          => $request->notes,
        ]);

        return redirect()->route('master.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        return redirect()->route('master.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}

