<?php

namespace App\Filament\Admin\Resources\Affiliates;

use App\Filament\Admin\Resources\Affiliates\Pages\CreateAffiliate;
use App\Filament\Admin\Resources\Affiliates\Pages\EditAffiliate;
use App\Filament\Admin\Resources\Affiliates\Pages\ListAffiliates;
use App\Filament\Admin\Resources\Affiliates\Schemas\AffiliateForm;
use App\Filament\Admin\Resources\Affiliates\Tables\AffiliatesTable;
use App\Models\Affiliate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AffiliateResource extends Resource
{
    protected static ?string $model = Affiliate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AffiliateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AffiliatesTable::configure($table);
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
            'index' => ListAffiliates::route('/'),
            'create' => CreateAffiliate::route('/create'),
            'edit' => EditAffiliate::route('/{record}/edit'),
        ];
    }
}
