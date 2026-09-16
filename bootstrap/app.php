<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\NormalizeCurrencyInputs::class,
        ]);
        $middleware->alias([
            'role'    => \App\Http\Middleware\RoleMiddleware::class,
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\PeriodClosedException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->isXmlHttpRequest() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error'   => 'period_closed',
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        });
    })->create();
