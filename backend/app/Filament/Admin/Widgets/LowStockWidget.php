<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LowStockWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('admin_dashboard_low_stock', now()->addMinutes(5), function () {
            // Otimização: Evitar carregar milhares de produtos na memória (N+1 oculto no get)
            // Resolvemos 100% no banco de dados usando whereHas com operador <
            $lowStockCount = \App\Models\Product::whereIn('status', ['active', 'published'])
                ->where(function ($query) {
                    $query->whereHas('stockItems', function ($q) {
                        $q->where('status', 'available');
                    }, '<', 5);
                })
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
