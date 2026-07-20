<?php

namespace App\Filament\Admin\Pages;

use App\Models\ReviewSetting;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class ReviewSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected string $view = 'filament.admin.pages.review-settings';
    public ?array $data = [];
    public static function getNavigationLabel(): string { return 'ConfiguraÃ§Ãµes de avaliaÃ§Ãµes'; }
    public static function getNavigationGroup(): ?string { return 'ConteÃºdo'; }
    public static function getNavigationIcon(): string { return 'heroicon-o-cog-6-tooth'; }

    public function mount(): void { $this->form->fill(ReviewSetting::current()->only(['enabled', 'auto_publish', 'suggested_phrases'])); }
    public function form(Schema $schema): Schema { return $schema->components([Toggle::make('enabled')->label('Ativar avaliaÃ§Ãµes'), Toggle::make('auto_publish')->label('Publicar automaticamente'), TagsInput::make('suggested_phrases')->label('Frases sugeridas')])->statePath('data'); }
    public function save(): void { ReviewSetting::current()->update($this->form->getState()); $this->dispatch('notify', status: 'success', message: 'ConfiguraÃ§Ãµes salvas.'); }
}
