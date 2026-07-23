<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard;
use Filament\Actions\Action;
use App\Models\Order;
use App\Filament\Admin\Widgets;

class StatisticsPage extends Dashboard
{
    public static function getNavigationIcon(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Estatísticas';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Vendas';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Estatísticas Avançadas';
    }

    protected static ?string $slug = 'statistics-page';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_orders')
                ->label('Exportar Pedidos (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    \App\Jobs\ExportOrdersJob::dispatch(auth()->id());
                    \Filament\Notifications\Notification::make()
                        ->title('Exportação Iniciada')
                        ->body('Você receberá uma notificação quando o arquivo estiver pronto.')
                        ->info()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\AverageTicketWidget::class,
            Widgets\CustomerRecurrenceWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            Widgets\SalesNetChartWidget::class,
            Widgets\PaymentGatewayDonutWidget::class,
            Widgets\ApprovalChargebackWidget::class,
            Widgets\RevenueByYearChartWidget::class,
            Widgets\TopCategoriesWidget::class,
            Widgets\TopCustomersWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
