<?php
namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class PaymentGatewayDonutWidget extends ChartWidget
{
    protected static ?string $heading = 'Vendas por Gateway';
    protected static ?int $sort = 4;
    protected static ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $data = Cache::remember('stat_payment_gateways', 300, function () {
            $gateways = Order::whereIn('status', ['paid', 'partially_refunded'])
                ->whereNotNull('gateway')
                ->selectRaw('gateway, COUNT(*) as count')
                ->groupBy('gateway')
                ->pluck('count', 'gateway');

            return [
                'labels' => $gateways->keys()->map(fn($g) => ucfirst($g))->toArray(),
                'values' => $gateways->values()->toArray()
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Pedidos',
                    'data' => $data['values'],
                    'backgroundColor' => [
                        'rgb(59, 130, 246)', // Blue
                        'rgb(16, 185, 129)', // Emerald
                        'rgb(245, 158, 11)', // Amber
                        'rgb(139, 92, 246)', // Violet
                        'rgb(236, 72, 153)', // Pink
                    ],
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
