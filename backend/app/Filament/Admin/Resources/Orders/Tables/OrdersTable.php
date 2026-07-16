<?php

namespace App\Filament\Admin\Resources\Orders\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('external_reference')
                    ->searchable(),
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
                EditAction::make(),
                \Filament\Tables\Actions\Action::make('refund')
                    ->label('Reembolsar')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Reembolso')
                    ->modalDescription('Você tem certeza que deseja reembolsar este pedido? Esta ação não pode ser desfeita e revogará as chaves digitais.')
                    ->visible(fn (\App\Models\Order $record) => $record->status === 'paid')
                    ->action(function (\App\Models\Order $record) {
                        try {
                            $gatewayName = 'efi'; // Ou pegar de algum log/DB se tivéssemos salvo gateway
                            $gateway = \App\Gateways\PaymentGatewayFactory::make($gatewayName);
                            // Supondo que External Reference é a transação ID
                            $gateway->refund($record->external_reference);

                            $record->update(['status' => 'refunded']);

                            // Revogar as chaves do pedido
                            foreach ($record->items as $item) {
                                foreach ($item->stockItems as $stockItem) {
                                    $stockItem->update(['status' => 'revoked']);
                                    app(\App\Services\InventoryService::class)->updateRedisCount($stockItem->product_id);
                                }
                            }

                            activity()
                                ->performedOn($record)
                                ->log("Pedido reembolsado manualmente e chaves revogadas");

                            \Filament\Notifications\Notification::make()
                                ->title('Pedido reembolsado e chaves revogadas.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro ao reembolsar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
