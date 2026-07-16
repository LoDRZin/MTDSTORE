<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RevalidateStorefrontCache implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $tag = 'products'
    ) {}

    public function handle(): void
    {
        $frontendUrl = config('services.frontend.url', 'http://localhost:3000');
        $secret = config('services.frontend.revalidate_secret');

        if (!$secret) {
            Log::warning("RevalidateStorefrontCache abortado: secret não configurado.");
            return;
        }

        try {
            $response = Http::post("{$frontendUrl}/api/revalidate", [
                'secret' => $secret,
                'tag' => $this->tag,
            ]);

            if ($response->failed()) {
                Log::error("Falha ao invalidar cache do storefront: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Erro de conexão ao tentar invalidar cache: " . $e->getMessage());
        }
    }
}
