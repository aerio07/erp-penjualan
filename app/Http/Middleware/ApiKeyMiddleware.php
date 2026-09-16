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
     * - Header: X-API-KEY
     * - Header: Authorization (Bearer <key>)
     * - Query string: ?api_key=<key>
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validKey = env('MOBILE_API_KEY', 'traderp_mobile_secret_key_2026');

        $providedKey = $request->header('X-API-KEY')
            ?: $request->header('x-api-key')
            ?: $request->bearerToken()
            ?: $request->query('api_key');

        if (!$providedKey || !hash_equals((string) $validKey, (string) $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing API Key.',
                'hint'    => 'Sertakan API Key via header X-API-KEY, Authorization: Bearer <key>, atau query param ?api_key=<key>',
            ], 401);
        }

        return $next($request);
    }
}