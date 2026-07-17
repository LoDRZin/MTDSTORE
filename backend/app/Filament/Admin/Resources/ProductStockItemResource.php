<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProductStockItemResource\Pages\ManageProductStockItems;
use App\Models\ProductStockItem;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn; // Filament v2 compatibility or TextColumn with badge()
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ProductStockItemResource extends Resource
{
    protected static ?string $model = ProductStockItem::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-key';
    }

    public static function getNavigationLabel(): string
    {
        return 'Estoque (Chaves)';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Chaves em Estoque';
    }

    public static function getModelLabel(): string
    {
        return 'Chave';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Loja';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Não usamos form para criar/editar um a um na listagem comum.
                // A importação em lote é uma Action no Header.
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('product.name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Disponível',
                        'sold' => 'Vendida',
                        'revoked' => 'Revogada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'sold' => 'primary',
                        'revoked' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('value')
                    ->label('Valor (Chave)')
                    ->formatStateUsing(fn () => '••••••••••••••••')
                    ->description('Oculto por segurança'),
                TextColumn::make('batch_id')
                    ->label('Lote (Importação)')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('addedBy.name')
                    ->label('Adicionado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Adicionada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'available' => 'Disponível',
                        'sold' => 'Vendida',
                        'revoked' => 'Revogada',
                    ]),
                SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->label('Filtrar Produto')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Action::make('reveal')
                    ->label('Revelar')
                    ->icon('heroicon-m-eye')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Revelar Chave Sensível')
                    ->modalDescription('Você está prestes a visualizar a chave original em texto puro. Esta ação será registrada em log e só deve ser feita se estritamente necessário. Tem certeza?')
                    ->modalSubmitActionLabel('Sim, revelar chave')
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->action(function (\App\Models\ProductStockItem $record) {
                        abort_unless(auth()->user()->hasRole('super_admin'), 403);
                        
                        // Log explícito
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->log('Visualizou a chave de estoque em texto puro');
                    })
                    ->modalContent(function (\App\Models\ProductStockItem $record) {
                        abort_unless(auth()->user()->hasRole('super_admin'), 403);
                        return view('filament.admin.components.reveal-key', ['key' => $record->value]);
                    }),

                Action::make('revoke')
                    ->label('Revogar')
                    ->icon('heroicon-m-archive-box-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ProductStockItem $record) => $record->status === 'available')
                    ->action(function (ProductStockItem $record) {
                        $record->update(['status' => 'revoked']);
                        // Atualiza cache Redis
                        app(\App\Services\InventoryService::class)->updateRedisCount($record->product_id);
                        
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->log('Revogou manualmente a chave');
                    }),

                DeleteAction::make()
                    ->visible(fn (ProductStockItem $record) => $record->status === 'available')
                    ->after(function (ProductStockItem $record) {
                        app(\App\Services\InventoryService::class)->updateRedisCount($record->product_id);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Remover Selecionadas')
                        ->action(function (Collection $records) {
                            $productsToUpdate = collect();
                            foreach ($records as $record) {
                                if ($record->status === 'available') {
                                    $productsToUpdate->push($record->product_id);
                                    $record->delete();
                                }
                            }
                            // Atualizar redis apenas dos produtos afetados
                            foreach ($productsToUpdate->unique() as $pid) {
                                app(\App\Services\InventoryService::class)->updateRedisCount($pid);
                            }
                        }),
                        
                    BulkAction::make('undo_batch')
                        ->label('Desfazer Lote')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Desfazer Importações em Lote')
                        ->modalDescription('Isso irá apagar APENAS as chaves que ainda estão disponíveis (status=available) e que pertencem aos mesmos lotes (batch_id) das chaves selecionadas. As vendidas permanecerão intactas.')
                        ->action(function (Collection $records) {
                            $batchIds = $records->pluck('batch_id')->filter()->unique();
                            $inventoryService = app(\App\Services\InventoryService::class);
                            foreach ($batchIds as $batch) {
                                $inventoryService->rollbackBatch($batch);
                            }
                        })
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'addedBy']));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProductStockItems::route('/'),
        ];
    }
}
