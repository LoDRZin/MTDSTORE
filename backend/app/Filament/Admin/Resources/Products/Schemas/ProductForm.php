<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        \Filament\Forms\Components\Tabs\Tab::make('Geral')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (\Filament\Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state))),

                                TextInput::make('slug')
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                Textarea::make('description')
                                    ->columnSpanFull(),

                                TextInput::make('image_url')
                                    ->label('URL da Imagem')
                                    ->url()
                                    ->nullable()
                                    ->columnSpanFull()
                                    ->helperText('Cole a URL de uma imagem para o produto. Ex: https://cdn.exemplo.com/produto.jpg'),

                                TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('R$'),

                                Select::make('status')
                                    ->options(['draft' => 'Rascunho', 'active' => 'Ativo', 'archived' => 'Arquivado'])
                                    ->default('draft')
                                    ->required(),

                                Select::make('categories')
                                    ->label('Categorias')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull()
                                    ->helperText('Selecione uma ou mais categorias para este produto.'),
                            ]),

                        \Filament\Forms\Components\Tabs\Tab::make('Estoque (Chaves Digitais)')
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('available_count')
                                    ->label('Quantidade Disponível (Redis)')
                                    ->content(fn (?App\Models\Product $record): string => $record ? app(\App\Services\InventoryService::class)->getAvailableCount($record->id) : '0'),

                                Textarea::make('bulk_keys')
                                    ->label('Colar chaves em lote (uma por linha)')
                                    ->helperText('As chaves coladas aqui serão criptografadas e adicionadas ao estoque ao salvar.')
                                    ->dehydrated(false) // MUITO IMPORTANTE: Não tenta salvar essa coluna na tabela products
                                    ->rows(10),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
