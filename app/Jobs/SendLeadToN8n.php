<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendLeadToN8n implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;
    public $tries = 5;

    public function backoff()
    {
        return [60, 120, 240, 480];
    }

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        $n8nUrl = env('N8N_MASTER_WEBHOOK_URL', 'http://localhost:5678/webhook/master-receiver');
        
        $response = Http::withoutVerifying()
            ->timeout(15)
            ->post($n8nUrl, $this->payload);

        if ($response->failed()) {
            Log::warning("N8n Webhook failed with status {$response->status()}");
            $response->throw();
        }
    }
}
