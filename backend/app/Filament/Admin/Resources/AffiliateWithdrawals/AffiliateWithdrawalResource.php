<?php

namespace App\Filament\Admin\Resources\AffiliateWithdrawals;

use App\Filament\Admin\Resources\AffiliateWithdrawals\Pages\CreateAffiliateWithdrawal;
use App\Filament\Admin\Resources\AffiliateWithdrawals\Pages\EditAffiliateWithdrawal;
use App\Filament\Admin\Resources\AffiliateWithdrawals\Pages\ListAffiliateWithdrawals;
use App\Filament\Admin\Resources\AffiliateWithdrawals\Schemas\AffiliateWithdrawalForm;
use App\Filament\Admin\Resources\AffiliateWithdrawals\Tables\AffiliateWithdrawalsTable;
use App\Models\AffiliateWithdrawal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AffiliateWithdrawalResource extends Resource
{
    protected static ?string $model = AffiliateWithdrawal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Afiliados';
    }

    public static function getNavigationLabel(): string
    {
        return 'Saques';
    }

    public static function getModelLabel(): string
    {
        return 'Saque';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Saques';
    }

    public static function form(Schema $schema): Schema
    {
        return AffiliateWithdrawalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AffiliateWithdrawalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffiliateWithdrawals::route('/'),
            'create' => CreateAffiliateWithdrawal::route('/create'),
            'edit' => EditAffiliateWithdrawal::route('/{record}/edit'),
        ];
    }
}
