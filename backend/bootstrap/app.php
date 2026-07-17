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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->api(append: [
            \App\Http\Middleware\CorrelationId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\App\Exceptions\OutOfStockException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'error' => [
                    'code' => 'OUT_OF_STOCK',
                    'message' => $e->getMessage(),
                    'trace_id' => $request->header('X-Correlation-ID', uniqid()),
                    'details' => []
                ]
            ], 409);
        });

        $exceptions->render(function (\App\Exceptions\InvalidCouponException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_COUPON',
                    'message' => $e->getMessage(),
                    'trace_id' => $request->header('X-Correlation-ID', uniqid()),
                    'details' => []
                ]
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Os dados fornecidos são inválidos.',
                        'trace_id' => $request->header('X-Correlation-ID', uniqid()),
                        'details' => $e->errors()
                    ]
                ], 422);
            }
        });

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // DEBUG TEMPORÁRIO PARA DESCOBRIR O 500 NO FILAMENT
            if (str_starts_with($request->path(), 'admin')) {
                return response(
                    "Error: " . get_class($e) . "\nMessage: " . $e->getMessage() . "\nFile: " . $e->getFile() . ":" . $e->getLine() . "\n\nTrace:\n" . $e->getTraceAsString(),
                    500
                )->header('Content-Type', 'text/plain');
            }

            if ($request->is('api/*') || $request->wantsJson()) {
                $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : 500;
                
                return response()->json([
                    'error' => [
                        'code' => $status === 404 ? 'NOT_FOUND' : ($status >= 500 ? 'INTERNAL_ERROR' : 'API_ERROR'),
                        'message' => $status >= 500 ? 'Erro interno no servidor.' : $e->getMessage(),
                        'trace_id' => $request->header('X-Correlation-ID', uniqid()),
                        'details' => config('app.debug') ? ['exception' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine()] : []
                    ]
                ], $status);
            }
        });
    })->create();
