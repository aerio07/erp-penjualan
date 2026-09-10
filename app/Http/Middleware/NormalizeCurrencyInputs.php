<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeCurrencyInputs
{
    /**
     * Handle an incoming request.
     * Automatically strip thousand separator dots from monetary/integer input fields.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $this->cleanThousandSeparators($input);
        $request->replace($input);

        return $next($request);
    }

    /**
     * Recursively strip thousand separator dots from Indonesian formatted numbers.
     */
    private function cleanThousandSeparators(array &$data): void
    {
        foreach ($data as $key => &$val) {
            if (is_array($val)) {
                $this->cleanThousandSeparators($val);
            } elseif (is_string($val)) {
                $trimmed = trim($val);
                // Matches format like 1.000, 15.000, 1.500.000, 100.000.000
                if (preg_match('/^\d{1,3}(\.\d{3})+$/', $trimmed)) {
                    $val = str_replace('.', '', $trimmed);
                }
            }
        }
    }
}
