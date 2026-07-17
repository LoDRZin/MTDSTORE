<?php

namespace App\Filament\Admin\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
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
                                ->helperText('Número de vezes que este cupom já foi utilizado.'),
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
                                ->helperText('Se vazio, será válido em todos os produtos.'),
                                
                            Select::make('categories')
                                ->relationship('categories', 'name')
                                ->multiple()
                                ->preload()
                                ->label('Restringir a Categorias')
                                ->helperText('Se vazio, não há restrição de categoria.'),
                                
                            Select::make('allowedUsers')
                                ->relationship('allowedUsers', 'name')
                                ->multiple()
                                ->searchable()
                                ->label('Restringir a Clientes Específicos')
                                ->helperText('Apenas estes clientes poderão usar. Se vazio, é público.'),
                                
                            Select::make('allowed_payment_methods')
                                ->multiple()
                                ->options([
                                    'pix' => 'Pix',
                                    'credit_card' => 'Cartão de Crédito',
                                    'boleto' => 'Boleto',
                                ])
                                ->label('Métodos de Pagamento Permitidos')
                                ->helperText('Se vazio, válido para qualquer método.')
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
