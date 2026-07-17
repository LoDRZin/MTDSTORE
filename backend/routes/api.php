<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// Autenticação com Google
Route::get('/auth/google/url', [\App\Http\Controllers\Api\AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [\App\Http\Controllers\Api\AuthController::class, 'googleCallback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    
    // Sincronização de Carrinho
    Route::post('/cart/sync', [\App\Http\Controllers\Api\AuthController::class, 'cartSync']);
    Route::get('/cart/load', [\App\Http\Controllers\Api\AuthController::class, 'cartLoad']);
});

// Rotas Públicas da Loja
Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
Route::get('/products/{slug}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);

// Carrinho e Cupons (Públicos mas usam API)
Route::post('/cart/calculate', [\App\Http\Controllers\Api\CartController::class, 'calculate']);
Route::post('/coupon/validate', [\App\Http\Controllers\Api\CouponController::class, 'validateCoupon']);

// Rotas de Checkout (Guest Checkout)
Route::post('/checkout', [\App\Http\Controllers\Api\CheckoutController::class, 'process'])
    ->middleware('throttle:checkout');

// Resgate de produtos digitais (com Rate Limiter agressivo)
Route::get('/orders/{uuid}/lookup', [\App\Http\Controllers\Api\OrderController::class, 'lookup'])
    ->middleware('throttle:3,1');

// Webhooks
Route::post('/webhooks/{gateway}', [\App\Http\Controllers\Api\WebhookController::class, 'handle']);

    // Rota de Sucesso (Acesso às chaves, protegida por Signed URL)
    Route::get('/orders/{order}/success', [\App\Http\Controllers\Api\SuccessController::class, 'show'])
        ->name('orders.success')
        ->middleware('signed');
        
    // Health check para monitoramento do worker da fila (Horizon)
    Route::get('/health/queue', function () {
        $lastHeartbeat = \Illuminate\Support\Facades\Redis::get('worker_heartbeat');
    
        if (!$lastHeartbeat) {
            return response()->json(['status' => 'unknown', 'reason' => 'no heartbeat found'], 503);
        }
    
        $secondsSinceLastBeat = now()->timestamp - (int) $lastHeartbeat;
    
        if ($secondsSinceLastBeat > 180) {
            return response()->json([
                'status' => 'stale',
                'seconds_since_last_beat' => $secondsSinceLastBeat,
            ], 503);
        }
    
        return response()->json(['status' => 'ok', 'seconds_since_last_beat' => $secondsSinceLastBeat]);
    });
});
