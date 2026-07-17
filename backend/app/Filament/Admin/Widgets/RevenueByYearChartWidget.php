<?php
namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RevenueByYearChartWidget extends ChartWidget
{
    protected ?string $heading = 'Receita Anual (Meses)';
    protected static ?int $sort = 6;
    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $data = Cache::remember('stat_revenue_year', 300, function () {
            $year = Carbon::now()->year;
            $orders = Order::whereIn('status', ['paid', 'partially_refunded'])
                ->whereYear('created_at', $year)
                ->selectRaw('MONTH(created_at) as month, SUM(total) as revenue')
                ->groupBy('month')
                ->pluck('revenue', 'month');

            $labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            $values = [];

            for ($i = 1; $i <= 12; $i++) {
                $values[] = $orders->get($i, 0);
            }

            return ['labels' => $labels, 'values' => $values];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Receita (R$)',
                    'data' => $data['values'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
