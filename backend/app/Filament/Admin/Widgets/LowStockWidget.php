<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LowStockWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $products = \App\Models\Product::where('status', 'active')->get();
        $lowStockCount = 0;
        
        $inventoryService = app(\App\Services\InventoryService::class);
        foreach ($products as $product) {
            $count = $inventoryService->getAvailableCount($product->id);
            if ($count < 5) { // Threshold for low stock
                $lowStockCount++;
            }
        }

        return [
            \Filament\Widgets\StatsOverviewWidget\Stat::make('Produtos em Baixo Estoque', $lowStockCount)
                ->description('Menos de 5 chaves disponíveis')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
