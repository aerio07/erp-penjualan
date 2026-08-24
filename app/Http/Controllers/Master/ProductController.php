<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class ProductController extends Controller
{
    use HasListFilters;

    public function index(Request $request): View
    {
        $query = Product::with('productCategory');

        $query = $this->applySearch($query, $request, ['sku', 'name', 'category', 'notes']);
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        } elseif ($request->filled('category')) {
            $query->where(function ($q) use ($request) {
                $q->where('category', $request->category)
                  ->orWhereHas('productCategory', fn($sq) => $sq->where('name', $request->category));
            });
        }
        $query = $this->applyFilter($query, $request, 'is_active');
        $query = $this->applySort($query, $request, ['sku', 'name', 'category', 'purchase_price', 'sell_price', 'min_stock', 'created_at'], 'name', 'asc');

        $perPage = (int) $request->get('per_page', 20);
        $products   = $query->paginate($perPage)->withQueryString();
        $categories = ProductCategory::orderBy('name')->get();

        return view('master.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        return view('master.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $existingProduct = $request->filled('sku') ? Product::where('sku', trim($request->sku))->first() : null;
        $customSkuMessage = $existingProduct 
            ? "Kode SKU \"{$request->sku}\" sudah digunakan oleh produk \"{$existingProduct->name}\". Setiap produk wajib memiliki kode SKU yang unik."
            : 'Kode SKU ":input" sudah terdaftar pada sistem. Gunakan kode SKU yang berbeda.';

        $request->validate([
            'sku'            => 'required|string|max:50|unique:products,sku',
            'name'           => 'required|string|max:255',
            'category_id'    => 'nullable|exists:product_categories,id',
            'unit'           => 'required|string|max:20',
            'purchase_price' => 'nullable|numeric|min:0',
            'sell_price'     => 'nullable|numeric|min:0',
            'min_stock'      => 'nullable|integer|min:0',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:3072',
        ], [
            'sku.required'            => 'Kode SKU produk wajib diisi.',
            'sku.unique'              => $customSkuMessage,
            'name.required'           => 'Nama produk wajib diisi.',
            'category_id.exists'      => 'Kategori produk yang dipilih tidak valid.',
            'unit.required'           => 'Satuan (unit) produk wajib diisi (contoh: pcs, box, kg).',
            'purchase_price.numeric'  => 'Harga beli harus berupa angka.',
            'purchase_price.min'      => 'Harga beli tidak boleh bernilai negatif.',
            'sell_price.numeric'      => 'Harga jual harus berupa angka.',
            'sell_price.min'          => 'Harga jual tidak boleh bernilai negatif.',
            'min_stock.integer'       => 'Batas minimum stok harus berupa angka bulat.',
            'min_stock.min'           => 'Batas minimum stok tidak boleh bernilai negatif.',
            'image.image'             => 'File foto produk harus berupa file gambar.',
            'image.mimes'             => 'Format foto yang didukung: JPG, JPEG, PNG, WEBP, atau SVG.',
            'image.max'               => 'Ukuran foto produk maksimal 3MB.',
        ]);

        $categoryId = $request->category_id;
        $categoryName = null;
        if ($categoryId) {
            $catModel = ProductCategory::find($categoryId);
            $categoryName = $catModel?->name;
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::create([
            'sku'            => trim($request->sku),
            'name'           => $request->name,
            'category_id'    => $categoryId,
            'category'       => $categoryName,
            'unit'           => $request->unit,
            'purchase_price' => $request->purchase_price ?? 0,
            'sell_price'     => $request->sell_price ?? 0,
            'min_stock'      => $request->min_stock ?? 0,
            'is_active'      => $request->boolean('is_active', true),
            'notes'          => $request->notes,
            'image'          => $imagePath,
        ]);

        return redirect()->route('master.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): View
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        $warehouseStocks = $warehouses->map(function ($wh) use ($product) {
            return [
                'warehouse'  => $wh,
                'on_hand'    => $product->onHandStock($wh->id),
                'reserved'   => $product->reservedStock($wh->id),
                'available'  => $product->availableStock($wh->id),
                'quarantine' => $product->quarantineStock($wh->id),
            ];
        });

        $totalOnHand     = $product->onHandStock();
        $totalReserved   = $product->reservedStock();
        $totalAvailable  = $product->availableStock();
        $totalIncoming   = $product->incomingStock();
        $totalQuarantine = $product->quarantineStock();
        $totalBackorder  = $product->backorderStock();

        $recentMovements = $product->stockMovements()
            ->with(['warehouse', 'user'])
            ->latest('movement_date')
            ->latest('id')
            ->limit(10)
            ->get();

        $recentSalesItems = $product->salesOrderItems()
            ->with(['salesOrder.customer'])
            ->whereHas('salesOrder')
            ->latest('id')
            ->limit(10)
            ->get();

        $recentPurchaseItems = $product->purchaseOrderItems()
            ->with(['purchaseOrder.supplier'])
            ->whereHas('purchaseOrder')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('master.products.show', compact(
            'product',
            'warehouseStocks',
            'totalOnHand',
            'totalReserved',
            'totalAvailable',
            'totalIncoming',
            'totalQuarantine',
            'totalBackorder',
            'recentMovements',
            'recentSalesItems',
            'recentPurchaseItems'
        ));
    }

    public function edit(Product $product): View
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        return view('master.products.create', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $existingProduct = $request->filled('sku') 
            ? Product::where('sku', trim($request->sku))->where('id', '!=', $product->id)->first() 
            : null;
            
        $customSkuMessage = $existingProduct 
            ? "Kode SKU \"{$request->sku}\" sudah digunakan oleh produk \"{$existingProduct->name}\". Setiap produk wajib memiliki kode SKU yang unik."
            : 'Kode SKU ":input" sudah terdaftar pada sistem. Gunakan kode SKU yang berbeda.';

        $request->validate([
            'sku'            => 'required|string|max:50|unique:products,sku,' . $product->id,
            'name'           => 'required|string|max:255',
            'category_id'    => 'nullable|exists:product_categories,id',
            'unit'           => 'required|string|max:20',
            'purchase_price' => 'nullable|numeric|min:0',
            'sell_price'     => 'nullable|numeric|min:0',
            'min_stock'      => 'nullable|integer|min:0',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:3072',
        ], [
            'sku.required'            => 'Kode SKU produk wajib diisi.',
            'sku.unique'              => $customSkuMessage,
            'name.required'           => 'Nama produk wajib diisi.',
            'category_id.exists'      => 'Kategori produk yang dipilih tidak valid.',
            'unit.required'           => 'Satuan (unit) produk wajib diisi (contoh: pcs, box, kg).',
            'purchase_price.numeric'  => 'Harga beli harus berupa angka.',
            'purchase_price.min'      => 'Harga beli tidak boleh bernilai negatif.',
            'sell_price.numeric'      => 'Harga jual harus berupa angka.',
            'sell_price.min'          => 'Harga jual tidak boleh bernilai negatif.',
            'min_stock.integer'       => 'Batas minimum stok harus berupa angka bulat.',
            'min_stock.min'           => 'Batas minimum stok tidak boleh bernilai negatif.',
            'image.image'             => 'File foto produk harus berupa file gambar.',
            'image.mimes'             => 'Format foto yang didukung: JPG, JPEG, PNG, WEBP, atau SVG.',
            'image.max'               => 'Ukuran foto produk maksimal 3MB.',
        ]);

        $categoryId = $request->category_id;
        $categoryName = null;
        if ($categoryId) {
            $catModel = ProductCategory::find($categoryId);
            $categoryName = $catModel?->name;
        }

        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = null;
        }

        $product->update([
            'sku'            => trim($request->sku),
            'name'           => $request->name,
            'category_id'    => $categoryId,
            'category'       => $categoryName,
            'unit'           => $request->unit,
            'purchase_price' => $request->purchase_price ?? 0,
            'sell_price'     => $request->sell_price ?? 0,
            'min_stock'      => $request->min_stock ?? 0,
            'is_active'      => $request->boolean('is_active'),
            'notes'          => $request->notes,
            'image'          => $imagePath,
        ]);

        return redirect()->route('master.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update([
            'is_active' => !$product->is_active,
        ]);

        $statusLabel = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status produk \"{$product->name}\" berhasil {$statusLabel}.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Cek apakah produk terkait dengan riwayat transaksi atau pergerakan stok
        $hasMovements      = $product->stockMovements()->exists();
        $hasSalesOrders    = $product->salesOrderItems()->exists();
        $hasPurchaseOrders = $product->purchaseOrderItems()->exists();
        $hasDemands        = $product->procurementDemands()->exists();

        if ($hasMovements || $hasSalesOrders || $hasPurchaseOrders || $hasDemands) {
            return back()->with('error', "Produk \"{$product->name}\" ({$product->sku}) tidak dapat dihapus karena sudah memiliki riwayat transaksi/stok. Anda dapat menonaktifkan produk ini sebagai gantinya.");
        }

        try {
            $productName = $product->name;
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $product->delete();
            return redirect()->route('master.products.index')
                ->with('success', "Produk \"{$productName}\" berhasil dihapus.");
        } catch (\Exception $e) {
            return back()->with('error', "Gagal menghapus produk: " . $e->getMessage());
        }
    }
}

