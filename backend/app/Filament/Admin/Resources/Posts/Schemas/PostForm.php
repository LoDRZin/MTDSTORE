<?php

namespace App\Filament\Admin\Resources\Posts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Postagem')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, ?string $state, callable $set) => $operation === 'create'
                                ? $set('slug', Str::slug($state ?? ''))
                                : null),
                        TextInput::make('author')
                            ->label('Autor')
                            ->required()
                            ->maxLength(255)
                            ->default(fn (): string => auth()->user()?->name ?? 'Admin'),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (string $state): string => Str::slug($state)),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Rascunho',
                                'published' => 'Publicado',
                            ])
                            ->default('draft')
                            ->required(),
                    ]),
                    FileUpload::make('cover_image')
                        ->label('Imagem de capa')
                        ->image()
                        ->directory('posts/covers')
                        ->columnSpanFull(),
                    RichEditor::make('content')
                        ->label('Conteúdo')
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
