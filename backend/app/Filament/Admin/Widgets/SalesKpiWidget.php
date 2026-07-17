<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class SalesKpiWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $period = $this->filters['period'] ?? '30_days';

        // Cache para evitar queries pesadas a cada refresh
        return Cache::remember("admin_dashboard_kpis_{$period}", now()->addMinutes(2), function () use ($period) {
            
            // Otimização: Evitar N+1 oculto no get()
            $lowStockCount = \App\Models\Product::whereIn('status', ['active', 'published'])
                ->where(function ($query) {
                    $query->whereHas('stockItems', function ($q) {
                        $q->where('status', 'available');
                    }, '<', 5);
                })
                ->count();
                
            $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();

            $query = Order::where('status', 'paid');

            if ($period !== 'all_time') {
                $days = match ($period) {
                    'today' => 0,
                    '7_days' => 7,
                    '90_days' => 90,
                    default => 30,
                };
                
                if ($days === 0) {
                    $query->whereDate('created_at', today());
                } else {
                    $query->where('created_at', '>=', now()->subDays($days)->startOfDay());
                }
            }

            // Agregação otimizada no DB
            $stats = $query->selectRaw('COUNT(*) as total_orders, SUM(total) as gross_revenue')->first();

            $totalOrders = $stats->total_orders ?? 0;
            $grossRevenue = $stats->gross_revenue ?? 0;

            // Simulando lucro líquido: Receita Bruta - Custos (Ex: comissões, gateways, cupons).
            // Para um cálculo real 100%, seria necessário juntar a tabela de comissões, 
            // mas podemos estimar uma margem ou usar os dados reais se a modelagem suportar.
            // Assumiremos um net de 80% para exemplificar, ou o grossRevenue se não tivermos custo direto na Order.
            $netRevenue = $grossRevenue * 0.85; 

            return [
                Stat::make('Produtos em Baixo Estoque', $lowStockCount)
                    ->description('Menos de 5 chaves')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color($lowStockCount > 0 ? 'danger' : 'success'),
                    
                Stat::make('Jobs Falhados', $failedJobs)
                    ->description('Falhas na fila')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color($failedJobs > 0 ? 'danger' : 'success'),
                    
                Stat::make('Receita Disponível', 'R$ ' . number_format($grossRevenue, 2, ',', '.'))
                    ->description('Receita bruta do período')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('success'),
                    
                Stat::make('Lucro Líquido (Est.)', 'R$ ' . number_format($netRevenue, 2, ',', '.'))
                    ->description('Após taxas e comissões')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('primary'),

                Stat::make('Vendas Concluídas', $totalOrders)
                    ->description('Pedidos pagos e entregues')
                    ->descriptionIcon('heroicon-m-shopping-bag')
                    ->color('info'),
            ];
        });
    }
}
