<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestHeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $label) {}

    public function handle(): void
    {
        // Simula um pouquinho de trabalho, só para aparecer no dashboard com alguma duração real
        usleep(300_000); // 0.3s

        Log::info("[TestHeartbeatJob] Processado com sucesso: {$this->label} na fila {$this->queue}");
    }
}
