<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações Básicas')
                    ->description('Defina o nome, slug e categorias deste produto.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nome do Produto')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (string $operation, $state, callable $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                            TextInput::make('slug')
                                ->label('URL Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->helperText('Ex: conta-de-netflix'),
                            Textarea::make('description')
                                ->label('Descrição Detalhada')
                                ->columnSpanFull(),
                            Select::make('categories')
                                ->label('Categorias')
                                ->relationship('categories', 'name')
                                ->multiple()
                                ->preload()
                                ->columnSpanFull(),
                        ]),
                    ]),

                Section::make('Mídia e Status')
                    ->description('Precificação, imagens e disponibilidade.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('price')
                                ->label('Preço Base')
                                ->required()
                                ->numeric()
                                ->prefix('R$'),
                            Select::make('status')
                                ->label('Status do Produto')
                                ->options([
                                    'draft' => 'Rascunho',
                                    'published' => 'Publicado',
                                    'archived' => 'Arquivado',
                                ])
                                ->default('draft')
                                ->required(),
                            FileUpload::make('image_url')
                                ->label('Imagem do Produto')
                                ->image()
                                ->directory('products')
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
