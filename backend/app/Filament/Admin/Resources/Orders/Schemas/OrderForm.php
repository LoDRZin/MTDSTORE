<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
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
                                ->preload(),
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

                Section::make('Itens e Chaves Entregues')
                    ->description('Lista de produtos comprados e as respectivas chaves de ativação enviadas ao cliente.')
                    ->components([
                        Repeater::make('orderItems')
                            ->relationship('orderItems')
                            ->label('')
                            ->components([
                                Placeholder::make('product.name')
                                    ->label('Produto')
                                    ->content(fn ($record) => $record?->product?->name ?? 'N/A'),
                                
                                Placeholder::make('price')
                                    ->label('Preço Unitário')
                                    ->content(fn ($record) => $record ? 'R$ ' . number_format($record->price, 2, ',', '.') : '-'),

                                Placeholder::make('stock_item.value')
                                    ->label('Chave Entregue')
                                    ->content(fn ($record) => $record?->stock_item?->value ?? 'Nenhuma chave atrelada'),
                            ])
                            ->columns(3)
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->disableItemMovement()
                    ])
                    ->visible(fn ($record) => $record !== null),
            ]);
    }
}
