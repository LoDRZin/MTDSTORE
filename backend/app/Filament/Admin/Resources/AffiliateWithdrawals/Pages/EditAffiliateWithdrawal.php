<?php

namespace App\Filament\Admin\Resources\AffiliateWithdrawals\Pages;

use App\Filament\Admin\Resources\AffiliateWithdrawals\AffiliateWithdrawalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAffiliateWithdrawal extends EditRecord
{
    protected static string $resource = AffiliateWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
