<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('period')
                            ->label('Período de Análise')
                            ->options([
                                'today' => 'Hoje',
                                '7_days' => 'Últimos 7 dias',
                                '30_days' => 'Últimos 30 dias',
                                '90_days' => 'Últimos 90 dias',
                                'all_time' => 'Todo o período',
                            ])
                            ->default('30_days'),
                    ])
                    ->columns(3),
            ]);
    }
}
