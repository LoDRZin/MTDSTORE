<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesTodayWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $hoje = \App\Models\Order::whereDate('created_at', today())->where('status', 'paid');
        $totalVendas = $hoje->sum('total');
        $pedidos = $hoje->count();

        return [
            \Filament\Widgets\StatsOverviewWidget\Stat::make('Vendas Hoje', 'R$ ' . number_format($totalVendas, 2, ',', '.'))
                ->description($pedidos . ' pedidos confirmados hoje')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
