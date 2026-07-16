<?php

namespace App\Filament\Admin\Resources\Affiliates\Pages;

use App\Filament\Admin\Resources\Affiliates\AffiliateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAffiliates extends ListRecords
{
    protected static string $resource = AffiliateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
