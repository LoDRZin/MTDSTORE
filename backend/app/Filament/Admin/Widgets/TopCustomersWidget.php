<?php
namespace App\Filament\Admin\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\User;

class TopCustomersWidget extends BaseWidget
{
    protected static ?int $sort = 8;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Top Clientes';

    public function table(Table $table): Table
    {
        $topIds = \Illuminate\Support\Facades\Cache::remember('admin_top_customers', 3600, function () {
            return User::query()
                ->withSum(['orders' => function($q) {
                    $q->whereIn('status', ['paid', 'partially_refunded']);
                }], 'total')
                ->having('orders_sum_total', '>', 0)
                ->orderByDesc('orders_sum_total')
                ->limit(5)
                ->pluck('id')
                ->toArray();
        });

        return $table
            ->query(
                User::query()
                    ->whereIn('id', $topIds)
                    ->withSum(['orders' => function($q) {
                        $q->whereIn('status', ['paid', 'partially_refunded']);
                    }], 'total')
                    ->withCount(['orders' => function($q) {
                        $q->whereIn('status', ['paid', 'partially_refunded']);
                    }])
                    ->orderByDesc('orders_sum_total')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail'),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Pedidos Pagos')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('orders_sum_total')
                    ->label('Total Gasto')
                    ->money('BRL')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
