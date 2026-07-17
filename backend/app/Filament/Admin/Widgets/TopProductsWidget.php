<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class TopProductsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Top 5 Produtos (Faturamento)';

    public function table(Table $table): Table
    {
        $period = $this->filters['period'] ?? '30_days';

        // We use caching via query caching or manual caching on a raw query.
        // But Filament tables expect an Eloquent Builder. 
        // We can do a subquery or join with orders/order_items to sort by revenue.
        // Since we want this to be optimized, we'll join order_items and orders.
        
        return $table
            ->query(
                Product::query()
                    ->select('products.*')
                    ->selectRaw('COALESCE(SUM(order_items.unit_price), 0) as total_revenue')
                    ->selectRaw('COUNT(order_items.id) as total_sales')
                    ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
                    ->leftJoin('orders', function ($join) use ($period) {
                        $join->on('order_items.order_id', '=', 'orders.id')
                             ->where('orders.status', '=', 'paid');
                             
                        if ($period !== 'all_time') {
                            $days = match ($period) {
                                'today' => 0,
                                '7_days' => 7,
                                '90_days' => 90,
                                default => 30,
                            };
                            if ($days === 0) {
                                $join->whereDate('orders.created_at', today());
                            } else {
                                $join->where('orders.created_at', '>=', now()->subDays($days)->startOfDay());
                            }
                        }
                    })
                    ->groupBy('products.id')
                    ->havingRaw('COUNT(order_items.id) > 0') // Only products with sales
                    ->orderByDesc('total_revenue')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Produto')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Unidades Vendidas')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Faturamento')
                    ->money('BRL')
                    ->color('success')
                    ->weight('bold'),
            ])
            ->paginated(false);
    }
}
