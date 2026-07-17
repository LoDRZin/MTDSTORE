<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QueueHealthWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('admin_dashboard_queue_health', now()->addMinutes(2), function () {
            $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();

            return [
                \Filament\Widgets\StatsOverviewWidget\Stat::make('Jobs Falhados', $failedJobs)
                    ->description('Jobs que falharam na fila')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color($failedJobs > 0 ? 'danger' : 'success'),
            ];
        });
    }
}
