<?php
namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SalesNetChartWidget extends ChartWidget
{
    protected ?string $heading = 'Vendas Líquidas (Últimos 30 dias)';
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '300s';
    public ?string $filter = '30'; // Default 30 days

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Últimos 7 dias',
            '30' => 'Últimos 30 dias',
            '90' => 'Últimos 3 meses',
            '365' => 'Último ano',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        
        $data = Cache::remember("stat_sales_net_chart_{$days}", 300, function () use ($days) {
            $orders = Order::whereIn('status', ['paid', 'partially_refunded'])
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
                ->groupBy('date')
                ->pluck('revenue', 'date');

            $labels = [];
            $values = [];

            for ($i = $days; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $labels[] = Carbon::parse($date)->format('d/m');
                $values[] = $orders->get($date, 0);
            }

            return ['labels' => $labels, 'values' => $values];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Vendas (R$)',
                    'data' => $data['values'],
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
