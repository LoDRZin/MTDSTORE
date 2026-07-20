<?php

namespace App\Filament\Admin\Resources\Reviews;

use App\Filament\Admin\Resources\Reviews\Pages\ListReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    public static function getNavigationLabel(): string { return 'AvaliaÃ§Ãµes'; }
    public static function getNavigationGroup(): ?string { return 'ConteÃºdo'; }
    public static function form(Schema $schema): Schema { return $schema; }

    public static function table(Table $table): Table
    {
        return $table->columns([
            \Filament\Tables\Columns\TextColumn::make('product.name')->label('Produto')->searchable(),
            \Filament\Tables\Columns\TextColumn::make('customer.email')->label('Cliente')->searchable(),
            \Filament\Tables\Columns\TextColumn::make('rating')->label('Nota')->badge()->color('warning'),
            \Filament\Tables\Columns\TextColumn::make('comment')->label('ComentÃ¡rio')->limit(60),
            \Filament\Tables\Columns\TextColumn::make('status')->badge()
                ->formatStateUsing(fn (string $state) => $state === 'published' ? 'Publicada' : 'Privada')
                ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray'),
            \Filament\Tables\Columns\TextColumn::make('created_at')->label('Recebida em')->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            \Filament\Tables\Filters\SelectFilter::make('status')->options(['private' => 'Privadas', 'published' => 'Publicadas']),
        ])->actions([
            \Filament\Actions\Action::make('publish')->label('Publicar')->color('success')
                ->visible(fn (Review $record) => $record->status !== 'published')
                ->action(fn (Review $record) => $record->update(['status' => 'published'])),
            \Filament\Actions\Action::make('hide')->label('Tornar privada')->color('gray')
                ->visible(fn (Review $record) => $record->status !== 'private')
                ->action(fn (Review $record) => $record->update(['status' => 'private'])),
        ])->modifyQueryUsing(fn ($query) => $query->with(['product:id,name', 'customer:id,email']));
    }

    public static function getPages(): array { return ['index' => ListReviews::route('/')]; }
}
