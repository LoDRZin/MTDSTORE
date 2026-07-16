<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Observers
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        \App\Models\ProductStockItem::observe(\App\Observers\ProductStockObserver::class);

        // Rate Limiters
        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('lookup', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip() . $request->input('email'));
        });

        // Protege o Horizon — apenas super_admin pode acessar
        Gate::define('viewHorizon', function ($user) {
            return $user->hasRole('super_admin');
        });

        // Propaga o trace_id para o Sentry
        if (app()->bound(\Sentry\State\HubInterface::class)) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                $correlationId = request()->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());
                $scope->setTag('trace_id', $correlationId);
            });
        }
    }
}
