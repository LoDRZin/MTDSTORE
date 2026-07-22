<?php

namespace App\Filament\Admin\Resources\Coupons\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('InformaÃ§Ãµes do cupom')->schema([
                Grid::make(2)->schema([
                    TextInput::make('code')
                        ->label('CÃ³digo')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (string $state): string => strtoupper(trim($state)))
                        ->suffixAction(Action::make('generate')
                            ->label('Gerar')
                            ->icon('heroicon-m-sparkles')
                            ->action(fn (callable $set) => $set('code', strtoupper(Str::random(8))))),
                    Select::make('type')
                        ->label('Tipo de desconto')
                        ->options(['fixed' => 'Fixo (R$)', 'percent' => 'Porcentagem (%)'])
                        ->default('percent')
                        ->required()
                        ->live(),
                    TextInput::make('value')
                        ->label('Valor do desconto')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(fn (callable $get) => $get('type') === 'percent' ? 100 : null)
                        ->prefix(fn (callable $get) => $get('type') === 'fixed' ? 'R$' : null)
                        ->suffix(fn (callable $get) => $get('type') === 'percent' ? '%' : null)
                        ->required(),
                    Toggle::make('active')->label('Cupom ativo')->default(true),
                ]),
            ]),
            Section::make('Limites e restriÃ§Ãµes')->schema([
                Grid::make(2)->schema([
                    Toggle::make('is_unlimited_uses')
                        ->label('Usos ilimitados')
                        ->default(fn ($record): bool => $record === null || $record->max_uses === null)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(fn (callable $set, bool $state) => $state ? $set('max_uses', null) : null),
                    TextInput::make('max_uses')
                        ->label('MÃ¡ximo de usos')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->disabled(fn (callable $get): bool => (bool) $get('is_unlimited_uses'))
                        ->required(fn (callable $get): bool => ! $get('is_unlimited_uses')),
                    Toggle::make('never_expires')
                        ->label('Nunca expira')
                        ->default(fn ($record): bool => $record === null || $record->expires_at === null)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(fn (callable $set, bool $state) => $state ? $set('expires_at', null) : null),
                    DateTimePicker::make('expires_at')
                        ->label('Data de expiraÃ§Ã£o')
                        ->native(false)
                        ->disabled(fn (callable $get): bool => (bool) $get('never_expires'))
                        ->required(fn (callable $get): bool => ! $get('never_expires')),
                    TextInput::make('min_purchase_amount')
                        ->label('Valor mÃ­nimo da compra')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('R$'),
                    TextInput::make('max_discount_value')
                        ->label('Teto do desconto')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('R$')
                        ->visible(fn (callable $get): bool => $get('type') === 'percent'),
                    Select::make('allowed_payment_methods')
                        ->label('Gateways permitidos')
                        ->multiple()
                        ->options([
                            'mercadopago' => 'Mercado Pago',
                            'stripe' => 'Stripe',
                            'efi' => 'EfÃ­',
                            'oxapay' => 'OxaPay',
                            'wise' => 'Wise',
                        ])
                        ->helperText('Vazio permite qualquer gateway.')
                        ->columnSpanFull(),
                    CheckboxList::make('products')
                        ->relationship('products', 'name')
                        ->bulkToggleable()
                        ->columns(2)
                        ->label('Produtos permitidos')
                        ->columnSpanFull(),
                    CheckboxList::make('categories')
                        ->relationship('categories', 'name')
                        ->bulkToggleable()
                        ->columns(2)
                        ->label('Categorias permitidas')
                        ->columnSpanFull(),
                    Select::make('allowedUsers')
                        ->relationship('allowedUsers', 'email')
                        ->multiple()
                        ->searchable()
                        ->label('Clientes permitidos')
                        ->helperText('Vazio permite qualquer cliente.')
                        ->columnSpanFull(),
                ]),
            ]),
        ]);
    }
}
