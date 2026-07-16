<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $keys = $this->data['bulk_keys'] ?? null;

        if ($keys) {
            $inventoryService = app(\App\Services\InventoryService::class);
            
            $count = $inventoryService->bulkImportKeys($this->record, $keys, auth()->id());
            
            if ($count > 0) {
                \Filament\Notifications\Notification::make()
                    ->title('Chaves importadas')
                    ->body($count . ' chaves foram adicionadas com sucesso.')
                    ->success()
                    ->send();
            }
        }
    }
}
