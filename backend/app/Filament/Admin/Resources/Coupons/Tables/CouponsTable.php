<?php

namespace App\Filament\Admin\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Código copiado!')
                    ->copyMessageDuration(1500)
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fixed' => 'Fixo (R$)',
                        'percentage' => 'Porcentagem (%)',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'info',
                        'percentage' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('value')
                    ->label('Valor')
                    ->formatStateUsing(fn ($record) => $record->type === 'percentage' ? "{$record->value}%" : "R$ {$record->value}")
                    ->sortable(),
                TextColumn::make('max_uses')
                    ->label('Máx. Usos')
                    ->formatStateUsing(fn ($state) => $state === null ? 'Ilimitado' : $state)
                    ->sortable(),
                TextColumn::make('uses_count')
                    ->label('Usos Realizados')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Validade')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
