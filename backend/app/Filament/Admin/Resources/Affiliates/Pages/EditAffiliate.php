<?php

namespace App\Filament\Admin\Resources\Affiliates\Pages;

use App\Filament\Admin\Resources\Affiliates\AffiliateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAffiliate extends EditRecord
{
    protected static string $resource = AffiliateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
