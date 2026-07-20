<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ReviewKpiWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $data = Cache::remember('reviews.kpis', 300, fn () => [
            'total' => Review::count(), 'published' => Review::where('status', 'published')->count(),
            'private' => Review::where('status', 'private')->count(), 'average' => Review::avg('rating'),
        ]);
        return [Stat::make('Nota média', number_format((float) $data['average'], 1)), Stat::make('Total', $data['total']), Stat::make('Publicadas', $data['published']), Stat::make('Privadas', $data['private'])];
    }
}
