<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalhes da Categoria')
                    ->description('Defina o nome, slug e uma imagem para representar a categoria.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nome da Categoria')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (string $operation, $state, callable $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                            TextInput::make('slug')
                                ->label('URL Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->helperText('Identificador único para a URL (ex: contas-de-minecraft)'),
                            Textarea::make('description')
                                ->label('Descrição')
                                ->columnSpanFull(),
                            FileUpload::make('image_url')
                                ->label('Imagem da Categoria')
                                ->image()
                                ->directory('categories')
                                ->columnSpanFull(),
                            Select::make('products')
                                ->label('Produtos Vinculados')
                                ->multiple()
                                ->relationship('products', 'name')
                                ->preload()
                                ->searchable()
                                ->columnSpanFull()
                                ->helperText('Selecione os produtos que farão parte desta categoria.'),
                        ]),
                    ]),

                Section::make('Hierarquia e Exibição')
                    ->description('Como e onde essa categoria será exibida na loja.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('parent_id')
                                ->label('Categoria Pai')
                                ->relationship('parent', 'name')
                                ->searchable()
                                ->preload()
                                ->helperText('Selecione uma categoria pai para transformá-la em uma subcategoria.'),
                            TextInput::make('order')
                                ->label('Ordem de Exibição')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->helperText('Categorias com números menores aparecem primeiro (ex: 0, 1, 2...).'),
                            Toggle::make('is_active')
                                ->label('Categoria Ativa?')
                                ->default(true)
                                ->required()
                                ->inline(false),
                        ]),
                    ]),
            ]);
    }
}
