<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->disabled(),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->disabled(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'awaiting_payment' => 'Awaiting payment',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'canceled' => 'Canceled',
                    ])
                    ->disabled(),
                TextInput::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                TextInput::make('external_reference')
                    ->disabled(),
                \Filament\Forms\Components\Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        \Filament\Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->disableOptionWhen(fn() => true),
                        \Filament\Forms\Components\TextInput::make('unit_price')
                            ->prefix('$'),
                        \Filament\Forms\Components\TextInput::make('stock_item_id')
                            ->label('Stock ID')
                    ])
                    ->columnSpanFull()
                    ->disabled(),
            ]);
    }
}
