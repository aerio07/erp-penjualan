<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;

class PeriodClosedException extends RuntimeException
{
    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request)
    {
        if ($request->expectsJson() || $request->isXmlHttpRequest() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'error'   => 'period_closed',
            ], 422);
        }

        return back()->withInput()->with('error', $this->getMessage());
    }
}
