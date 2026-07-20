<?php

namespace App\Filament\Admin\Resources\Posts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Capa')
                    ->circular(),
                TextColumn::make('title')
                    ->label('TÃ­tulo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('author')
                    ->label('Autor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'published' ? 'Publicado' : 'Rascunho')
                    ->color(fn (string $state): string => $state === 'published' ? 'success' : 'gray'),
                TextColumn::make('updated_at')
                    ->label('Atualizada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Rascunho',
                    'published' => 'Publicado',
                ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
