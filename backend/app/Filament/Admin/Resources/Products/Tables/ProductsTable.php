<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('available_count')
                    ->label('Estoque (Redis)')
                    ->getStateUsing(fn (\App\Models\Product $record): string => (string) app(\App\Services\InventoryService::class)->getAvailableCount($record->id)),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\Action::make('rollbackBatch')
                    ->label('Desfazer Lote')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->form([
                        \Filament\Forms\Components\Select::make('batch_id')
                            ->label('Selecione o Lote (Batch ID)')
                            ->options(function (\App\Models\Product $record) {
                                return \App\Models\ProductStockItem::where('product_id', $record->id)
                                    ->whereNotNull('batch_id')
                                    ->select('batch_id', \Illuminate\Support\Facades\DB::raw('count(*) as total, MAX(created_at) as data'))
                                    ->groupBy('batch_id')
                                    ->orderBy('data', 'desc')
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        return [$item->batch_id => "Lote {$item->batch_id} - {$item->total} chaves ({$item->data})"];
                                    })
                                    ->toArray();
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, \App\Models\Product $record): void {
                        app(\App\Services\InventoryService::class)->rollbackBatch($data['batch_id']);
                        \Filament\Notifications\Notification::make()
                            ->title('Lote desfeito com sucesso')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
