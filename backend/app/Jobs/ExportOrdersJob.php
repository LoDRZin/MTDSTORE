<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Storage;

class ExportOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        public int $userId
    ) {}

    public function handle(): void
    {
        $filename = 'exports/pedidos_' . date('Y-m-d_H-i-s') . '.csv';
        Storage::disk('local')->put($filename, "\xEF\xBB\xBF"); // UTF-8 BOM
        
        $file = fopen(storage_path('app/' . $filename), 'a');
        fputcsv($file, ['ID', 'Cliente', 'E-mail', 'Valor (R$)', 'Status', 'Gateway', 'Data']);

        Order::query()->with('customer')->chunkById(500, function ($orders) use ($file) {
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

        $user = \App\Models\User::find($this->userId);
        if ($user) {
            Notification::make()
                ->title('Exportação Concluída')
                ->body('O arquivo CSV de pedidos já está pronto para download.')
                ->success()
                ->actions([
                    Action::make('download')
                        ->label('Baixar CSV')
                        ->url(route('admin.download-export', ['file' => base64_encode($filename)]))
                        ->button(),
                ])
                ->sendToDatabase($user);
        }
    }
}
