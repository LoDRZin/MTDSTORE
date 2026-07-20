<?php

namespace App\Filament\Admin\Pages;

use App\Models\StoreSetting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Config;

class SettingsPage extends Page
{
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cog-8-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    public static function getNavigationLabel(): string
    {
        return 'Configurações da Loja';
    }

    public function getTitle(): string 
    {
        return 'Configurações da Loja';
    }
    
    protected string $view = 'filament.admin.pages.settings-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(StoreSetting::current()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tab::make('Geral')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                TextInput::make('store_name')
                                    ->label('Nome da Loja')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('cnpj')
                                    ->label('CNPJ')
                                    ->mask('99.999.999/9999-99')
                                    ->maxLength(255),
                                RichEditor::make('description')
                                    ->label('Descrição da Loja')
                                    ->columnSpanFull(),
                                Grid::make(2)->schema([
                                    Toggle::make('maintenance_mode')
                                        ->label('Modo de Manutenção')
                                        ->helperText('Exibe uma página de manutenção no frontend.'),
                                    Toggle::make('require_login')
                                        ->label('Login Obrigatório')
                                        ->helperText('Obriga o cliente a estar logado para acessar a loja.'),
                                ]),
                            ]),
                        Tab::make('Contatos')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                TextInput::make('contact_email')
                                    ->label('E-mail de Contato')
                                    ->email()
                                    ->maxLength(255),
                                KeyValue::make('social_links')
                                    ->label('Redes Sociais')
                                    ->keyLabel('Rede (ex: whatsapp, instagram)')
                                    ->valueLabel('URL / Link'),
                                Toggle::make('show_business_hours')
                                    ->label('Exibir Horário de Atendimento')
                                    ->reactive(),
                                Repeater::make('business_hours')
                                    ->label('Horários de Atendimento')
                                    ->schema([
                                        Select::make('day')
                                            ->label('Dia')
                                            ->options([
                                                'segunda' => 'Segunda-feira',
                                                'terca' => 'Terça-feira',
                                                'quarta' => 'Quarta-feira',
                                                'quinta' => 'Quinta-feira',
                                                'sexta' => 'Sexta-feira',
                                                'sabado' => 'Sábado',
                                                'domingo' => 'Domingo',
                                            ])
                                            ->required(),
                                        TextInput::make('hours')
                                            ->label('Horário (ex: 09:00 as 18:00)')
                                            ->required(),
                                        Toggle::make('closed')
                                            ->label('Fechado'),
                                    ])
                                    ->columns(3)
                                    ->visible(fn ($get) => $get('show_business_hours')),
                            ]),
                        Tab::make('Pagamentos')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Gateways de Pagamento')
                                    ->description('Status dos gateways configurados no sistema. Para alterar as credenciais, atualize o arquivo .env do backend.')
                                    ->schema([
                                        Placeholder::make('stripe_status')
                                            ->label('Stripe')
                                            ->content(config('services.stripe.secret') ? '✅ Configurado' : '❌ Não configurado'),
                                        Placeholder::make('pix_status')
                                            ->label('MercadoPago (PIX)')
                                            ->content(config('services.mercadopago.access_token') ? '✅ Configurado' : '❌ Não configurado'),
                                        Placeholder::make('efi_status')
                                            ->label('Efí (PIX)')
                                            ->content(config('services.efi.client_id') ? '✅ Configurado' : '❌ Não configurado'),
                                        Placeholder::make('oxapay_status')
                                            ->label('OxaPay (Crypto)')
                                            ->content(config('services.oxapay.merchant_key') ? '✅ Configurado' : '❌ Não configurado'),
                                    ])->columns(2),
                            ]),
                        Tab::make('Tema')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Grid::make(2)->schema([
                                    ColorPicker::make('primary_color')
                                        ->label('Cor Primária')
                                        ->required(),
                                    ColorPicker::make('secondary_color')
                                        ->label('Cor Secundária')
                                        ->required(),
                                ]),
                                FileUpload::make('logo_path')
                                    ->label('Logo')
                                    ->image()
                                    ->directory('settings'),
                                FileUpload::make('favicon_path')
                                    ->label('Favicon')
                                    ->image()
                                    ->directory('settings'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $setting = StoreSetting::current();
        $setting->update($data);

        Notification::make()
            ->title('Configurações salvas')
            ->success()
            ->send();
    }
}
