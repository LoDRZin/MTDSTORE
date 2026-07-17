<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Models\Order;
use App\Filament\Admin\Widgets;

class StatisticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Estatísticas';
    protected static ?string $title = 'Estatísticas Avançadas';
    protected static ?string $navigationGroup = 'Vendas';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament-panels::pages.dashboard';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_orders')
                ->label('Exportar Pedidos (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return response()->streamDownload(function () {
                        $file = fopen('php://output', 'w');
                        // Cabeçalhos do CSV
                        fputcsv($file, ['ID', 'Cliente', 'E-mail', 'Valor (R$)', 'Status', 'Gateway', 'Data']);

                        Order::query()->with('customer')->chunk(500, function ($orders) use ($file) {
                            foreach ($orders as $order) {
                                fputcsv($file, [
                                    $order->id,
                                    $order->customer?->name ?? 'Anônimo',
                                    $order->customer?->email ?? 'N/A',
                                    number_format($order->total, 2, ',', ''),
                                    $order->status,
                                    $order->gateway ?? 'N/A',
                                    $order->created_at->format('Y-m-d H:i:s'),
                                ]);
                            }
                        });
                        fclose($file);
                    }, 'pedidos_' . date('Y-m-d_H-i-s') . '.csv');
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
