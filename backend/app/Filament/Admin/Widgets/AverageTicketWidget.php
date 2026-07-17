<?php
namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class AverageTicketWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '300s';

    protected function getStats(): array
    {
        $avg = Cache::remember('stat_average_ticket', 300, function () {
            return Order::whereIn('status', ['paid', 'partially_refunded'])->avg('total') ?? 0;
        });

        $totalPaid = Cache::remember('stat_total_paid_count', 300, function () {
            return Order::whereIn('status', ['paid', 'partially_refunded'])->count();
        });

        return [
            Stat::make('Ticket Médio', 'R$ ' . number_format($avg, 2, ',', '.'))
                ->description('Valor médio das vendas aprovadas')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
                
            Stat::make('Total de Pedidos Aprovados', number_format($totalPaid, 0, ',', '.'))
                ->description('Volume total em unidades')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),
        ];
    }
}
