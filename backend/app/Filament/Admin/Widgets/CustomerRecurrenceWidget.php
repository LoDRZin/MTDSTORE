<?php
namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class CustomerRecurrenceWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '300s';

    protected function getStats(): array
    {
        $recurrence = Cache::remember('stat_customer_recurrence', 300, function () {
            // Conta pedidos pagos agrupados por cliente
            $counts = Order::whereNotNull('customer_id')
                ->whereIn('status', ['paid', 'partially_refunded'])
                ->selectRaw('customer_id, COUNT(*) as count')
                ->groupBy('customer_id')
                ->pluck('count');

            $new = $counts->filter(fn ($c) => $c == 1)->count();
            $recurring = $counts->filter(fn ($c) => $c > 1)->count();
            $total = $new + $recurring;

            if ($total === 0) return ['new' => 0, 'recurring' => 0, 'rate' => 0];

            return [
                'new' => $new,
                'recurring' => $recurring,
                'rate' => round(($recurring / $total) * 100, 1)
            ];
        });

        return [
            Stat::make('Taxa de Recorrência', $recurrence['rate'] . '%')
                ->description("{$recurrence['recurring']} retornaram / {$recurrence['new']} novos")
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }
}
