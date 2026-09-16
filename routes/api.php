<?php

use App\Http\Controllers\Api\ProductApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Mobile E-Commerce (Protected by API Key)
|--------------------------------------------------------------------------
*/

// ── Test Koneksi & Verifikasi API Key ─────────────────────────────────────
Route::middleware('api.key')->get('/ping', function (Request $request) {
    return response()->json([
        'success'   => true,
        'message'   => 'API Connected! API Key valid.',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// ── Mobile E-Commerce Endpoints (Dilindungi API Key) ─────────────────────
Route::middleware('api.key')->group(function () {

    // Katalog Produk (List & Detail)
    Route::get('/products',           [ProductApiController::class, 'index']);
    Route::get('/products/{product}', [ProductApiController::class, 'show']);

    // Kategori Produk
    Route::get('/categories', [ProductApiController::class, 'categories']);
});