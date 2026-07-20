<?php

namespace App\Filament\Admin\Resources\Affiliates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AffiliateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Afiliado')
                    ->description('Vincule um usuário e defina seu código exclusivo.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('user_id')
                                ->label('Usuário Afiliado')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('code')
                                ->label('Código do Afiliado')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->suffixAction(
                                    Action::make('generate')
                                        ->icon('heroicon-m-sparkles')
                                        ->label('Gerar')
                                        ->action(function (callable $set) {
                                            $set('code', strtoupper(Str::random(8)));
                                        })
                                ),
                        ]),
                    ]),

                Section::make('Financeiro e Status')
                    ->description('Controle de saldo, comissões e ativação da conta.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('balance')
                                ->label('Saldo Atual')
                                ->required()
                                ->numeric()
                                ->default(0.0)
                                ->prefix('R$')
                                ->helperText('O saldo é atualizado automaticamente pelas vendas, mas pode ser editado pelo admin.'),
                            TextInput::make('commission_rate')
                                ->label('Taxa de Comissão (%)')
                                ->required()
                                ->numeric()
                                ->default(10.0)
                                ->suffix('%')
                                ->helperText('Porcentagem que o afiliado ganha por venda aprovada.'),
                            TextInput::make('min_withdrawal')
                                ->label('Saque Mínimo (R$)')
                                ->numeric()
                                ->default(50.0)
                                ->prefix('R$')
                                ->helperText('Valor mínimo para o afiliado solicitar saque.'),
                            TextInput::make('cookie_duration_days')
                                ->label('Duração do Cookie (dias)')
                                ->numeric()
                                ->integer()
                                ->default(30)
                                ->suffix('dias')
                                ->helperText('Por quantos dias o código de afiliado rastreia conversões.'),
                            Toggle::make('active')
                                ->label('Conta Ativa?')
                                ->default(true)
                                ->required()
                                ->inline(false),
                        ]),
                    ]),
            ]);
    }
}
