<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ChatbotAnswerLibraryVectorService
{
    private const TABLE =
        'chatbot_answer_library_vectors';

    private $embeddings;

    public function __construct(
        ChatbotSemanticEmbeddingService $embeddings
    ) {
        $this->embeddings = $embeddings;
    }

    public function isAvailable()
    {
        try {
            return Schema::hasTable(self::TABLE);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function sync($libraryId, $question)
    {
        $libraryId = (int) $libraryId;
        $question = trim((string) $question);

        if (
            $libraryId <= 0
            || $question === ''
            || !$this->isAvailable()
        ) {
            return false;
        }

        try {
            $vector = $this->embeddings->embed($question);

            if (!is_array($vector) || empty($vector)) {
                return false;
            }

            $now = now();

            DB::table(self::TABLE)->updateOrInsert(
                ['library_id' => $libraryId],
                [
                    'question_hash' => hash(
                        'sha256',
                        $question
                    ),
                    'model' => (string) config(
                        'chatbot.semantic.model',
                        'text-embedding-3-small'
                    ),
                    'dimensions' => count($vector),
                    'embedding_json' => json_encode(
                        $vector,
                        JSON_PRESERVE_ZERO_FRACTION
                    ),
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning(
                '[ChatbotAnswerLibraryVectorService] '
                    . 'Không đồng bộ được vector',
                [
                    'library_id' => $libraryId,
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    public function delete($libraryId)
    {
        if (!$this->isAvailable()) {
            return;
        }

        try {
            DB::table(self::TABLE)
                ->where('library_id', (int) $libraryId)
                ->delete();
        } catch (\Throwable $e) {
            Log::warning(
                '[ChatbotAnswerLibraryVectorService] '
                    . 'Không xóa được vector',
                [
                    'library_id' => (int) $libraryId,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    public function getMany(array $libraryIds)
    {
        if (!$this->isAvailable() || empty($libraryIds)) {
            return [];
        }

        try {
            $rows = DB::table(self::TABLE)
                ->whereIn(
                    'library_id',
                    array_values(array_unique(array_map(
                        'intval',
                        $libraryIds
                    )))
                )
                ->get();

            $result = [];

            foreach ($rows as $row) {
                $vector = json_decode(
                    (string) $row->embedding_json,
                    true
                );

                if (!is_array($vector) || empty($vector)) {
                    continue;
                }

                $result[(int) $row->library_id] =
                    array_map('floatval', $vector);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning(
                '[ChatbotAnswerLibraryVectorService] '
                    . 'Không đọc được vector',
                ['error' => $e->getMessage()]
            );

            return [];
        }
    }
}
