<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalhes do Pedido')
                    ->description('Os pedidos são gerados automaticamente. A edição manual é desabilitada para evitar inconsistências.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('uuid')
                                ->label('ID do Pedido (UUID)')
                                ->required(),
                            Select::make('customer_id')
                                ->label('Cliente')
                                ->relationship('customer', 'name'),
                            Select::make('status')
                                ->label('Status do Pedido')
                                ->options([
                                    'pending' => 'Pendente',
                                    'paid' => 'Pago',
                                    'failed' => 'Falhou',
                                    'cancelled' => 'Cancelado',
                                ])
                                ->required()
                                ->default('pending'),
                            TextInput::make('total')
                                ->label('Valor Total')
                                ->required()
                                ->numeric()
                                ->prefix('R$'),
                            TextInput::make('external_reference')
                                ->label('Referência Externa (Gateway)')
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
