<?php

namespace App\Filament\Admin\Resources\AffiliateWithdrawals\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AffiliateWithdrawalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('affiliate_id')
                    ->relationship('affiliate.user', 'name')
                    ->label('Afiliado')
                    ->disabled()
                    ->required(),
                TextInput::make('amount')
                    ->label('Valor Solicitado (R$)')
                    ->disabled()
                    ->required()
                    ->numeric(),
                \Filament\Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        'paid' => 'Pago',
                    ])
                    ->required()
                    ->default('pending'),
                Textarea::make('admin_notes')
                    ->label('Notas do Administrador')
                    ->columnSpanFull(),
            ]);
    }
}
