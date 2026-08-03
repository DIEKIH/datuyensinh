<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatbotSemanticEmbeddingService
{
    private const ENDPOINT =
        'https://api.openai.com/v1/embeddings';

    public function isEnabled()
    {
        return (bool) config(
            'chatbot.semantic.enabled',
            true
        ) && trim((string) config(
            'services.openai.key'
        )) !== '';
    }

    /**
     * Tạo vector ngữ nghĩa. Mọi lỗi đều fail-open.
     */
    public function embed($text)
    {
        $text = trim((string) $text);

        if ($text === '' || !$this->isEnabled()) {
            return null;
        }

        $model = (string) config(
            'chatbot.semantic.model',
            'text-embedding-3-small'
        );

        $cacheKey = 'chatbot_embedding:'
            . hash('sha256', $model . '|' . $text);

        try {
            return Cache::remember(
                $cacheKey,
                now()->addHours(12),
                function () use ($text, $model) {
                    return $this->requestEmbedding(
                        $text,
                        $model
                    );
                }
            );
        } catch (\Throwable $e) {
            Log::warning(
                '[ChatbotSemanticEmbeddingService] '
                    . 'Không tạo được embedding',
                [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]
            );

            return null;
        }
    }

    public function cosineSimilarity(
        array $left,
        array $right
    ) {
        $count = count($left);

        if ($count === 0 || $count !== count($right)) {
            return null;
        }

        $dot = 0.0;
        $leftNorm = 0.0;
        $rightNorm = 0.0;

        for ($index = 0; $index < $count; $index++) {
            $leftValue = (float) $left[$index];
            $rightValue = (float) $right[$index];

            $dot += $leftValue * $rightValue;
            $leftNorm += $leftValue * $leftValue;
            $rightNorm += $rightValue * $rightValue;
        }

        if ($leftNorm <= 0.0 || $rightNorm <= 0.0) {
            return null;
        }

        return $dot / (
            sqrt($leftNorm) * sqrt($rightNorm)
        );
    }

    private function requestEmbedding($text, $model)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '
                . config('services.openai.key'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->withOptions([
                'timeout' => (int) config(
                    'chatbot.semantic.timeout',
                    20
                ),
                'connect_timeout' => (int) config(
                    'chatbot.semantic.connect_timeout',
                    5
                ),
            ])
            ->post(self::ENDPOINT, [
                'model' => $model,
                'input' => $text,
                'encoding_format' => 'float',
            ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Embeddings API HTTP '
                    . $response->status()
                    . ': '
                    . mb_substr(
                        $response->body(),
                        0,
                        500,
                        'UTF-8'
                    )
            );
        }

        $vector = $response->json('data.0.embedding');

        if (!is_array($vector) || empty($vector)) {
            throw new RuntimeException(
                'Embeddings API không trả vector hợp lệ.'
            );
        }

        return array_map('floatval', $vector);
    }
}
