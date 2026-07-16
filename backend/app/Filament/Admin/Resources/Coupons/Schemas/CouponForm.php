<?php

namespace App\Filament\Admin\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Cupom')
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->helperText('Ex: BLACKFRIDAY20 — será salvo em maiúsculas.')
                            ->afterStateUpdated(fn (\Filament\Forms\Set $set, ?string $state) => $set('code', strtoupper($state ?? '')))
                            ->live(onBlur: true),

                        Select::make('type')
                            ->options(['percent' => '% Percentual', 'fixed' => 'R$ Valor Fixo'])
                            ->required()
                            ->native(false),

                        TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Para "Percentual", use o valor sem o % (ex: 20 = 20%). Para "Valor Fixo", use o valor em R$.'),

                        TextInput::make('min_order_value')
                            ->label('Valor Mínimo do Pedido (R$)')
                            ->numeric()
                            ->nullable()
                            ->minValue(0)
                            ->helperText('Deixe vazio para não exigir valor mínimo.'),

                        TextInput::make('max_discount_value')
                            ->label('Desconto Máximo (R$)')
                            ->numeric()
                            ->nullable()
                            ->minValue(0)
                            ->helperText('Cap máximo do desconto. Útil para cupons de % com teto. Deixe vazio para ilimitado.'),
                    ])->columns(2),

                Section::make('Limites e Validade')
                    ->schema([
                        TextInput::make('max_uses')
                            ->label('Usos Máximos')
                            ->numeric()
                            ->nullable()
                            ->helperText('Deixe vazio para usos ilimitados.'),

                        TextInput::make('uses_count')
                            ->label('Usos Realizados')
                            ->numeric()
                            ->default(0)
                            ->disabled(),

                        DateTimePicker::make('expires_at')
                            ->label('Expira em')
                            ->nullable(),

                        Toggle::make('active')
                            ->label('Ativo')
                            ->required()
                            ->default(true),
                    ])->columns(2),

                Section::make('Restrição por Produtos')
                    ->description('Se nenhum produto for selecionado, o cupom vale para TODOS os produtos da loja.')
                    ->schema([
                        Select::make('products')
                            ->label('Produtos Permitidos')
                            ->relationship('products', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
