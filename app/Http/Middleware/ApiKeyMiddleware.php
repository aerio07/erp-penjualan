<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     * Validates API Key from:
     * - Header: X-API-KEY / api-key / Authorization
     * - Query string: ?api_key=<key> / ?key=<key>
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Daftar key yang diperbolehkan (ambil dari config, env, dan default keys)
        $validKeys = array_filter(array_unique([
            trim((string) config('services.mobile_api_key', '')),
            trim((string) env('MOBILE_API_KEY', '')),
            'traderp_mobile_key_2026',
            'traderp_mobile_secret_key_2026',
        ]));

        // Ambil token dari berbagai kemungkinan header & parameter
        $providedKey = $request->header('X-API-KEY')
            ?: $request->header('x-api-key')
            ?: $request->header('API-KEY')
            ?: $request->header('api-key')
            ?: $request->header('apikey')
            ?: $request->bearerToken()
            ?: $request->query('api_key')
            ?: $request->query('apiKey')
            ?: $request->query('key')
            ?: $request->input('api_key');

        $providedKey = trim((string) $providedKey);

        $isValid = false;
        if (!empty($providedKey)) {
            foreach ($validKeys as $vk) {
                if (!empty($vk) && hash_equals($vk, $providedKey)) {
                    $isValid = true;
                    break;
                }
            }
        }

        if (!$isValid) {
            return response()->json([
                'success'       => false,
                'message'       => 'Unauthorized: Invalid or missing API Key.',
                'accepted_keys' => ['traderp_mobile_key_2026', 'traderp_mobile_secret_key_2026'],
                'provided_key'  => !empty($providedKey) ? $providedKey : null,
                'how_to_send'   => [
                    'header'      => 'X-API-KEY: traderp_mobile_key_2026',
                    'bearer'      => 'Authorization: Bearer traderp_mobile_key_2026',
                    'query_param' => '?api_key=traderp_mobile_key_2026',
                ],
            ], 401);
        }

        return $next($request);
    }
}