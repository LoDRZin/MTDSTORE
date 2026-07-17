<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LowStockWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('admin_dashboard_low_stock', now()->addMinutes(5), function () {
            // Otimização: Evitar N+1 queries e timeouts do Redis ao carregar o dashboard
            // Usamos withCount() e filtramos via collection (rápido e compatível com PGSQL)
            $lowStockCount = \App\Models\Product::whereIn('status', ['active', 'published'])
                ->withCount(['stockItems' => function ($query) {
                    $query->where('status', 'available');
                }])
                ->get()
                ->filter(fn($product) => $product->stock_items_count < 5)
                ->count();

            return [
                \Filament\Widgets\StatsOverviewWidget\Stat::make('Produtos em Baixo Estoque', $lowStockCount)
                    ->description('Menos de 5 chaves disponíveis')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color($lowStockCount > 0 ? 'danger' : 'success'),
            ];
        });
    }
}
