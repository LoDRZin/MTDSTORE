<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class QueueHeartbeat extends Command
{
    protected $signature = 'queue:heartbeat';
    protected $description = 'Grava timestamp no Redis confirmando que o worker está vivo e processando';

    public function handle(): int
    {
        Cache::put('worker_heartbeat', now()->timestamp);
        $this->info('Heartbeat registrado: ' . now()->toDateTimeString());

        return self::SUCCESS;
    }
}
