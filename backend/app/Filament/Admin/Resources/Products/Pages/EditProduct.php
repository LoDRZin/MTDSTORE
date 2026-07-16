<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $keys = $this->data['bulk_keys'] ?? null;

        if ($keys) {
            $inventoryService = app(\App\Services\InventoryService::class);
            
            // Apenas repassa a string para o Service, que faz o split e trim
            $count = $inventoryService->bulkImportKeys($this->record, $keys, auth()->id());
            
            if ($count > 0) {
                \Filament\Notifications\Notification::make()
                    ->title('Chaves importadas')
                    ->body($count . ' chaves foram adicionadas com sucesso ao estoque.')
                    ->success()
                    ->send();
            }
        }
    }
}
