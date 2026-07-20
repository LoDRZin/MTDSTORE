<?php

namespace App\Filament\Admin\Resources\Webhooks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WebhookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome do Webhook')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->label('Endpoint URL')
                    ->url()
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('events')
                    ->label('Eventos a escutar')
                    ->multiple()
                    ->options([
                        'order.created' => 'Pedido Criado',
                        'order.paid' => 'Pagamento Confirmado',
                        'order.chargeback' => 'Chargeback Recebido',
                        'order.refunded' => 'Pedido Reembolsado',
                        'product.created' => 'Produto Criado',
                    ])
                    ->required(),
                TextInput::make('secret')
                    ->label('Secret (Opcional)')
                    ->helperText('Usado para assinar o payload via HMAC-SHA256.')
                    ->password()
                    ->revealable()
                    ->maxLength(255),
                Toggle::make('active')
                    ->label('Ativo')
                    ->default(true),
            ]);
    }
}
