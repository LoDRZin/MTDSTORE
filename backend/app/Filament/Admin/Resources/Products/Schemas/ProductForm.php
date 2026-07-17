<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
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

                        Section::make('Variações do Produto')
                            ->description('Crie opções diferentes para este produto (ex: Level 10, Level 50).')
                            ->schema([
                                Repeater::make('variants')
                                    ->relationship('variants')
                                    ->label('Variações')
                                    ->addActionLabel('Adicionar Variação')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('name')
                                                ->label('Nome da Variação')
                                                ->required()
                                                ->placeholder('Ex: Plano Mensal, Conta Level Max'),
                                            TextInput::make('price')
                                                ->label('Preço da Variação')
                                                ->numeric()
                                                ->minValue(0)
                                                ->prefix('R$')
                                                ->required(),
                                        ]),
                                    ])
                                    ->defaultItems(0)
                                    ->reorderable(true)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Mídia e Status')
                            ->description('Precificação, imagens e disponibilidade.')
                            ->schema([
                                Grid::make(1)->schema([
                                    TextInput::make('price')
                                        ->label('Preço Base')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('R$'),
                                    Select::make('status')
                                        ->label('Status do Produto')
                                        ->options([
                                            'draft' => 'Rascunho',
                                            'active' => 'Publicado',
                                            'archived' => 'Arquivado',
                                        ])
                                        ->default('draft')
                                        ->required(),
                                    Select::make('delivery_type')
                                        ->label('Tipo de Entrega')
                                        ->options([
                                            'unique_key' => 'Chave Única (Estoque Digital)',
                                            'file_download' => 'Download de Arquivo',
                                        ])
                                        ->default('unique_key')
                                        ->required()
                                        ->live(),
                                    FileUpload::make('file_path')
                                        ->label('Arquivo para Download')
                                        ->directory('downloads')
                                        ->preserveFilenames()
                                        ->visible(fn (\Filament\Forms\Get $get) => $get('delivery_type') === 'file_download')
                                        ->required(fn (\Filament\Forms\Get $get) => $get('delivery_type') === 'file_download')
                                        ->columnSpanFull(),
                                    FileUpload::make('image_url')
                                        ->label('Imagem do Produto')
                                        ->image()
                                        ->directory('products')
                                        ->columnSpanFull(),
                                    RichEditor::make('post_purchase_instructions')
                                        ->label('Instruções Pós-Compra')
                                        ->helperText('Exibido ao cliente logo após o pagamento ser aprovado.')
                                        ->columnSpanFull(),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
