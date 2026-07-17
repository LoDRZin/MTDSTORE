<?php

namespace App\Filament\Admin\Resources\ProductStockItemResource\Pages;

use App\Filament\Admin\Resources\ProductStockItemResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class ManageProductStockItems extends ManageRecords
{
    protected static string $resource = ProductStockItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulk_import')
                ->label('Importar Lote de Chaves')
                ->icon('heroicon-m-document-arrow-down')
                ->color('primary')
                ->modalHeading('Importar Chaves (Estoque)')
                ->modalDescription('Cole as chaves abaixo (uma por linha). O sistema irá criptografá-las automaticamente no banco de dados e ignorar duplicatas exatas.')
                ->form([
                    Select::make('product_id')
                        ->label('Produto')
                        ->options(Product::where('status', '!=', 'archived')->pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->required(),
                    Select::make('variant_id')
                        ->label('Variação (Obrigatório se o produto tiver variações)')
                        ->options(fn (\Filament\Forms\Get $get) => 
                            $get('product_id') 
                                ? \App\Models\ProductVariant::where('product_id', $get('product_id'))->pluck('name', 'id') 
                                : []
                        )
                        ->visible(fn (\Filament\Forms\Get $get) => 
                            $get('product_id') && \App\Models\ProductVariant::where('product_id', $get('product_id'))->exists()
                        )
                        ->required(fn (\Filament\Forms\Get $get) => 
                            $get('product_id') && \App\Models\ProductVariant::where('product_id', $get('product_id'))->exists()
                        )
                        ->searchable(),
                    Textarea::make('bulk_keys')
                        ->label('Chaves (uma por linha)')
                        ->rows(10)
                        ->required()
                        ->placeholder("XXXX-XXXX-XXXX-XXXX\nYYYY-YYYY-YYYY-YYYY"),
                ])
                ->action(function (array $data) {
                    $product = Product::find($data['product_id']);
                    $inventoryService = app(\App\Services\InventoryService::class);
                    
                    $count = $inventoryService->bulkImportKeys(
                        $product,
                        $data['bulk_keys'],
                        auth()->id(),
                        $data['variant_id'] ?? null
                    );

                    if ($count > 0) {
                        Notification::make()
                            ->title('Importação concluída!')
                            ->body("{$count} chaves foram importadas com sucesso para {$product->name}.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Nenhuma chave nova importada.')
                            ->body('Todas as chaves informadas já existiam ou o campo estava vazio.')
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
}
