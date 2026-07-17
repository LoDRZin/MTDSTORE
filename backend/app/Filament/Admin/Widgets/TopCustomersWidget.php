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
        return $table
            ->query(
                User::query()
                    ->withSum(['orders' => function($q) {
                        $q->whereIn('status', ['paid', 'partially_refunded']);
                    }], 'total')
                    ->withCount(['orders' => function($q) {
                        $q->whereIn('status', ['paid', 'partially_refunded']);
                    }])
                    ->having('orders_sum_total', '>', 0)
                    ->orderByDesc('orders_sum_total')
                    ->limit(5)
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
