<?php

namespace App\Filament\Admin\Resources\Customers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Cliente')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Status de Banimento')
                    ->description('Preencha para banir esta conta. Deixe em branco para desbanir.')
                    ->schema([
                        DateTimePicker::make('banned_at')
                            ->label('Banido em')
                            ->nullable()
                            ->displayFormat('d/m/Y H:i'),
                        Textarea::make('ban_reason')
                            ->label('Motivo do Ban')
                            ->nullable()
                            ->columnSpanFull()
                            ->rows(3),
                    ])->columns(2),
            ]);
    }
}
