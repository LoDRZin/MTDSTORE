<?php

namespace App\Filament\Admin\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Cupom')
                    ->description('Configuração principal do código e tipo de desconto.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('code')
                                ->label('Código do Cupom')
                                ->required()
                                ->suffixAction(
                                    Action::make('generate')
                                        ->icon('heroicon-m-sparkles')
                                        ->label('Gerar')
                                        ->action(function (callable $set) {
                                            $set('code', strtoupper(Str::random(8)));
                                        })
                                ),
                            Select::make('type')
                                ->label('Tipo de Desconto')
                                ->options([
                                    'fixed' => 'Fixo (R$)',
                                    'percentage' => 'Porcentagem (%)',
                                ])
                                ->required()
                                ->live(),
                            TextInput::make('value')
                                ->label('Valor do Desconto')
                                ->required()
                                ->numeric()
                                ->prefix(fn (callable $get) => $get('type') === 'percentage' ? null : 'R$')
                                ->suffix(fn (callable $get) => $get('type') === 'percentage' ? '%' : null),
                            Toggle::make('active')
                                ->label('Cupom Ativo?')
                                ->default(true)
                                ->required()
                                ->inline(false),
                        ]),
                    ]),

                Section::make('Restrições e Limites')
                    ->description('Regras opcionais para limitar o uso deste cupom.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('max_uses')
                                ->label('Máximo de Usos (Total)')
                                ->numeric()
                                ->placeholder('Ilimitado')
                                ->helperText('Deixe em branco para uso ilimitado.'),
                            TextInput::make('uses_count')
                                ->label('Usos Atuais')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Controlado automaticamente pelo sistema.'),
                            DateTimePicker::make('expires_at')
                                ->label('Data de Expiração'),
                            TextInput::make('min_order_value')
                                ->label('Valor Mín. do Pedido')
                                ->numeric()
                                ->prefix('R$'),
                            TextInput::make('max_discount_value')
                                ->label('Desconto Máximo')
                                ->numeric()
                                ->prefix('R$')
                                ->helperText('Limita o valor máximo (útil para %)')
                                ->hidden(fn (callable $get) => $get('type') !== 'percentage'),
                            Select::make('products')
                                ->relationship('products', 'name')
                                ->multiple()
                                ->preload()
                                ->label('Restringir a Produtos Específicos')
                                ->helperText('Se vazio, será válido em todos.')
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
