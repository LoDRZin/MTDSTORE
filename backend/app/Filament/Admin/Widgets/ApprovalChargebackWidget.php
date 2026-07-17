<?php
namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class ApprovalChargebackWidget extends ChartWidget
{
    protected ?string $heading = 'Aprovações vs Chargebacks';
    protected static ?int $sort = 5;
    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $data = Cache::remember('stat_approval_chargeback', 300, function () {
            $paid = Order::whereIn('status', ['paid', 'partially_refunded'])->count();
            $chargeback = Order::where('status', 'chargeback')->count();
            $failed = Order::where('status', 'failed')->count();
            $refunded = Order::where('status', 'refunded')->count();

            return [
                'labels' => ['Aprovados', 'Chargeback', 'Falhos', 'Reembolsados'],
                'values' => [$paid, $chargeback, $failed, $refunded]
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Status do Pedido',
                    'data' => $data['values'],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',  // Green
                        'rgb(239, 68, 68)',  // Red
                        'rgb(156, 163, 175)', // Gray
                        'rgb(245, 158, 11)',  // Amber
                    ],
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
