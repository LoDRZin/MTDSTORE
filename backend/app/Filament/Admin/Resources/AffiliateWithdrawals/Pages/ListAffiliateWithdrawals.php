<?php

namespace App\Filament\Admin\Resources\AffiliateWithdrawals\Pages;

use App\Filament\Admin\Resources\AffiliateWithdrawals\AffiliateWithdrawalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAffiliateWithdrawals extends ListRecords
{
    protected static string $resource = AffiliateWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
