<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Receita e Vendas';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $period = $this->filters['period'] ?? '30_days';

        return Cache::remember("admin_dashboard_chart_{$period}", now()->addMinutes(5), function () use ($period) {
            $query = Order::where('status', 'paid');
            $days = 30;

            if ($period !== 'all_time') {
                $days = match ($period) {
                    'today' => 1,
                    '7_days' => 7,
                    '90_days' => 90,
                    default => 30,
                };
                $query->where('created_at', '>=', now()->subDays($days)->startOfDay());
            } else {
                $days = 180; // limite de visualização
                $query->where('created_at', '>=', now()->subDays($days)->startOfDay());
            }

            // Agrupa por dia usando truncamento compatível com o banco
            $sales = $query->select(
                DB::raw('CAST(created_at AS DATE) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('CAST(created_at AS DATE)'))
            ->orderBy('date')
            ->get();

            // Preencher dias vazios
            $labels = [];
            $revenueData = [];
            $countData = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $dateString = now()->subDays($i)->format('Y-m-d');
                $labels[] = now()->subDays($i)->format('d/m');
                
                $dayStat = $sales->firstWhere('date', $dateString);
                $revenueData[] = $dayStat ? $dayStat->revenue : 0;
                $countData[] = $dayStat ? $dayStat->count : 0;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Receita (R$)',
                        'data' => $revenueData,
                        'backgroundColor' => '#10b981', // emerald-500
                        'borderColor' => '#10b981',
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'Qtd. Vendas',
                        'data' => $countData,
                        'backgroundColor' => '#3b82f6', // blue-500
                        'borderColor' => '#3b82f6',
                        'yAxisID' => 'y1',
                        'type' => 'line',
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
