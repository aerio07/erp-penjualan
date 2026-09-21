<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    /**
     * GET /api/products
     * Daftar produk untuk mobile e-commerce (ringan & cepat).
     * Query params:
     *   - q           : search by nama / SKU
     *   - category_id : filter kategori
     *   - per_page    : jumlah per halaman (default 10)
     */
    public function index(Request $request)
    {
        $query = Product::with('productCategory')
            ->where('is_active', true);

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('sku', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $perPage = (int) $request->get('per_page', 10);
        $products = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $products->map(fn($p) => $this->formatProductListItem($p)),
            'meta'    => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    /**
     * GET /api/products/{id}
     * Detail lengkap satu produk termasuk spesifikasi dimensi & rincian stok.
     */
    public function show(Product $product)
    {
        if (!$product->is_active) {
            return response()->json(['success' => false, 'message' => 'Produk tidak tersedia.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatProductDetail($product),
        ]);
    }

    /**
     * GET /api/categories
     * Daftar kategori produk untuk filter di mobile.
     */
    public function categories()
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Format untuk katalog list produk (ringan).
     */
    private function formatProductListItem(Product $p): array
    {
        $categoryName = $p->productCategory?->name ?? $p->category ?? 'Uncategorized';
        $sellingPrice = (float) $p->getEffectivePrice('online');

        return [
            'id'            => $p->id,
            'sku'           => $p->sku,
            'name'          => $p->name,
            'title'         => $p->name,
            'category'      => $categoryName,
            'category_name' => $categoryName,
            'category_id'   => $p->category_id,
            'unit'          => $p->unit,
            'price'         => $sellingPrice,
            'sell_price'    => (float) $p->sell_price,
            'price_online'  => $p->price_online ? (float) $p->price_online : null,
            'price_offline' => $p->price_offline ? (float) $p->price_offline : null,
            'stock'         => $p->availableStock(),
            'image'         => $p->image_url,
            'image_url'     => $p->image_url,
            'is_active'     => (bool) $p->is_active,
        ];
    }

    /**
     * Format untuk detail produk lengkap (dimensi, berat, deskripsi, rincian stok).
     */
    private function formatProductDetail(Product $p): array
    {
        $base = $this->formatProductListItem($p);

        $length = $p->length_cm ? (float) $p->length_cm : null;
        $width  = $p->width_cm  ? (float) $p->width_cm  : null;
        $height = $p->height_cm ? (float) $p->height_cm : null;
        $weight = $p->weight_kg ? (float) $p->weight_kg : null;

        return array_merge($base, [
            'description'   => $p->notes ?? '',
            'notes'         => $p->notes,

            // Dimensi dan Berat (Hanya di Detail Produk)
            'length'        => $length,
            'length_cm'     => $length,
            'width'         => $width,
            'width_cm'      => $width,
            'height'        => $height,
            'height_cm'     => $height,
            'weight'        => $weight,
            'weight_kg'     => $weight,
            'weight_gram'   => $weight ? (int) round($weight * 1000) : null,
            'dimensions'    => [
                'length' => $length,
                'width'  => $width,
                'height' => $height,
                'weight' => $weight,
            ],

            'stock_detail'  => [
                'on_hand'   => $p->onHandStock(),
                'available' => $p->availableStock(),
                'reserved'  => $p->reservedStock(),
            ],
            'min_stock'     => $p->min_stock,
        ]);
    }
}