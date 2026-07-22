<?php

namespace App\Filament\Admin\Resources\Customers\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('orders_count')
                    ->label('Pedidos')
                    ->counts('orders')
                    ->sortable()
                    ->badge(),
                IconColumn::make('banned_at')
                    ->label('Banido?')
                    ->boolean()
                    ->trueIcon('heroicon-o-no-symbol')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->getStateUsing(fn ($record) => !is_null($record->banned_at)),
                TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('banned')
                    ->label('Status da Conta')
                    ->placeholder('Todos')
                    ->trueLabel('Banidos')
                    ->falseLabel('Ativos')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('banned_at'),
                        false: fn (Builder $query) => $query->whereNull('banned_at'),
                    ),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                Action::make('ban')
                    ->label('Banir')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => is_null($record->banned_at))
                    ->form([
                        Textarea::make('ban_reason')
                            ->label('Motivo do Ban')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        abort_unless(auth()->user()->hasRole(['admin', 'super_admin']), 403);
                        $record->update([
                            'banned_at' => now(),
                            'ban_reason' => $data['ban_reason'],
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title("Cliente {$record->name} foi banido.")
                            ->danger()
                            ->send();
                    }),
                Action::make('unban')
                    ->label('Desbanir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => !is_null($record->banned_at))
                    ->action(function ($record) {
                        abort_unless(auth()->user()->hasRole(['admin', 'super_admin']), 403);
                        $record->update([
                            'banned_at' => null,
                            'ban_reason' => null,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title("Cliente {$record->name} foi desbanido.")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
