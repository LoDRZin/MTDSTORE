<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    protected static ?string $title = 'Itens e Chaves Entregues';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produto'),
                
                TextColumn::make('price')
                    ->label('Preço Unitário')
                    ->money('BRL'),

                TextColumn::make('stock_item.value')
                    ->label('Chave Entregue')
                    ->formatStateUsing(fn ($record) => $record->stock_item_id ? '••••••••••••••••' : 'Nenhuma chave atrelada')
                    ->description('Mascarado por segurança'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Action::make('reveal')
                    ->label('Revelar')
                    ->icon('heroicon-m-eye')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Revelar Chave Sensível')
                    ->modalDescription('Você está prestes a visualizar a chave original em texto puro. Esta ação será registrada em log.')
                    ->visible(fn ($record) => $record->stock_item_id && auth()->user()->hasRole('super_admin'))
                    ->action(function ($record) {
                        abort_unless(auth()->user()->hasRole('super_admin'), 403);
                        if ($record->stock_item) {
                            activity()
                                ->performedOn($record->stock_item)
                                ->causedBy(auth()->user())
                                ->withProperties(['order_id' => $record->order_id])
                                ->log('Visualizou a chave de estoque no pedido em texto puro');
                        }
                    })
                    ->modalContent(function ($record) {
                        abort_unless(auth()->user()->hasRole('super_admin'), 403);
                        return view('filament.admin.components.reveal-key', ['key' => $record->stock_item->value ?? '']);
                    }),
            ])
            ->bulkActions([
                //
            ])
            ->modifyQueryUsing(fn ($query) => $query->with(['product', 'stock_item']));
    }
}
