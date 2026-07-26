<?php

namespace App\Jobs;

use App\Models\Baiviet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendArticleToN8n implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Retry 3 lần
    public $backoff = [2, 5, 10]; // Chờ 2s, 5s, 10s giữa các lần retry
    protected $baiviet;

    public function __construct(Baiviet $baiviet)
    {
        $this->baiviet = $baiviet;
    }

    private function htmlToPlainText($html)
    {
        $html = html_entity_decode($html);
        // Chèn xuống dòng ở các thẻ block trước khi strip, tránh dính chữ
        $html = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6])\s*\/?>/i', "\n", $html);
        $text = strip_tags($html);
        // Gom nhiều dòng trống liên tiếp lại
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    private function buildPublicUrl(?string $path, string $publicUrl): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim($publicUrl, '/') . '/' . ltrim($path, '/');
    }

    public function handle()
    {
        $webhookUrl = env('N8N_MASTER_WEBHOOK_URL', 'http://localhost:5678/webhook/master-receiver');
        
        $publicUrl = config('app.public_url');
        if (!$publicUrl) {
            Log::error('N8N Webhook Failed: Missing public URL configuration');
            throw new \Exception('Missing public URL configuration');
        }
        $publicUrl = rtrim((string) $publicUrl, '/');

        // Chuẩn hóa Category
        $category = 'Tin tức';
        if (isset($this->baiviet->chude)) {
            $category = $this->baiviet->chude->tenchude ?? 'Tin tức';
        }

        $slug = ltrim((string) ($this->baiviet->slug ?? $this->baiviet->id), '/');
        $articleUrl = "{$publicUrl}/{$slug}";
        
        $thumbnailUrl = $this->buildPublicUrl($this->baiviet->image_url ?? $this->baiviet->thumbnail, $publicUrl);

        // Chuẩn hóa dữ liệu
        $payload = [
            'event_type' => 'new_article',
            'data' => [
                'id' => $this->baiviet->id,
                'title' => $this->baiviet->tieude ?? $this->baiviet->title ?? '',
                'content' => $this->baiviet->noidung ?? $this->baiviet->content ?? '',
                'plain_content' => $this->htmlToPlainText($this->baiviet->noidung ?? $this->baiviet->content ?? ''),
                'thumbnail' => $thumbnailUrl,
                'category' => $category,
                'auto_publish' => $this->baiviet->auto_publish ?? false,
                'url' => $articleUrl,
                'public_url' => $publicUrl,
                'publish_time' => now()->toDateTimeString(),
            ]
        ];

        try {
            $response = Http::timeout(15)->post($webhookUrl, $payload);

            if ($response->failed()) {
                Log::error('N8N Webhook Failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'id' => $this->baiviet->id,
                    'url' => $articleUrl,
                    'webhook' => $webhookUrl
                ]);
                throw new \Exception('N8N Webhook returned error: ' . $response->status());
            } else {
                Log::info('N8N Webhook Success', [
                    'id' => $this->baiviet->id,
                    'url' => $payload['data']['url'],
                    'thumbnail' => $payload['data']['thumbnail'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('N8N Webhook Exception: ' . $e->getMessage());
            throw $e; // Throw để queue tự retry theo config
        }
    }
}
