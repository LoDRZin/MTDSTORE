<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
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
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable(),
                            Select::make('status')
                                ->label('Status do Pedido')
                                ->options([
                                    'pending' => 'Pendente',
                                    'awaiting_payment' => 'Aguardando Pagamento',
                                    'paid' => 'Pago',
                                    'failed' => 'Falhou',
                                    'refunded' => 'Reembolsado',
                                    'partially_refunded' => 'Parcialmente Reembolsado',
                                    'chargeback' => 'Chargeback',
                                ])
                                ->required()
                                ->default('pending'),
                            TextInput::make('total')
                                ->label('Valor Total')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('R$'),
                            TextInput::make('external_reference')
                                ->label('Referência Externa (Gateway)')
                                ->nullable()
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
