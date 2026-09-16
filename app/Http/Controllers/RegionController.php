<?php

namespace App\Http\Controllers;

use App\Services\RegionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Get list of all provinces.
     */
    public function provinces(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => RegionService::getProvinces(),
        ]);
    }

    /**
     * Get list of regencies/cities in a province.
     */
    public function regencies(string $provinceId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => RegionService::getRegencies($provinceId),
        ]);
    }

    /**
     * Get list of districts (kecamatan) in a regency.
     */
    public function districts(string $regencyId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => RegionService::getDistricts($regencyId),
        ]);
    }

    /**
     * Get list of villages (kelurahan/desa) in a district.
     */
    public function villages(string $districtId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => RegionService::getVillages($districtId),
        ]);
    }
}
