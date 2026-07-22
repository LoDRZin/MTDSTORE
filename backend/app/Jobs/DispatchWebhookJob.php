<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $event,
        public readonly array $payload
    ) {}

    public function handle(): void
    {
        $webhooks = Webhook::where('active', \Illuminate\Support\Facades\DB::raw('true'))
            ->whereJsonContains('events', $this->event)
            ->get();

        foreach ($webhooks as $webhook) {
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'MTDStore-Webhook/1.0',
            ];

            $jsonPayload = json_encode($this->payload);

            if ($webhook->secret) {
                $headers['X-Signature'] = hash_hmac('sha256', $jsonPayload, $webhook->secret);
            }

            try {
                $response = Http::withHeaders($headers)
                    ->timeout(10)
                    ->post($webhook->url, $this->payload);

                WebhookLog::create([
                    'webhook_id' => $webhook->id,
                    'event' => $this->event,
                    'payload' => $this->payload,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            } catch (\Exception $e) {
                WebhookLog::create([
                    'webhook_id' => $webhook->id,
                    'event' => $this->event,
                    'payload' => $this->payload,
                    'response_status' => 500,
                    'response_body' => $e->getMessage(),
                ]);
            }
        }
    }
}
