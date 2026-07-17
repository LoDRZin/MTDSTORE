<?php
namespace App\Filament\Admin\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Category;

class TopCategoriesWidget extends BaseWidget
{
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Categorias Mais Vendidas';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Category::query()
                    ->join('category_product', 'categories.id', '=', 'category_product.category_id')
                    ->join('order_items', 'category_product.product_id', '=', 'order_items.product_id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereIn('orders.status', ['paid', 'partially_refunded'])
                    ->selectRaw('categories.id, categories.name, SUM(order_items.price * order_items.quantity) as revenue, SUM(order_items.quantity) as total_items')
                    ->groupBy('categories.id', 'categories.name')
                    ->orderByDesc('revenue')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Categoria')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_items')
                    ->label('Itens Vendidos'),
                Tables\Columns\TextColumn::make('revenue')
                    ->label('Receita Gerada')
                    ->money('BRL'),
            ])
            ->paginated(false);
    }
}
