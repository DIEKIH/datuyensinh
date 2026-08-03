<?php

namespace App\Console\Commands;

use App\Services\ChatbotAnswerLibraryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildChatbotLibraryMetadata extends Command
{
    protected $signature =
        'chatbot:library-rebuild {--chunk=200}';

    protected $description =
        'Tạo lại normalized_question và metadata cho kho chatbot';

    private $library;

    public function __construct(
        ChatbotAnswerLibraryService $library
    ) {
        parent::__construct();
        $this->library = $library;
    }

    public function handle()
    {
        $chunk = max(20, (int) $this->option('chunk'));
        $updated = 0;
        $invalid = 0;

        DB::table('chatbot_answer_library')
            ->where('is_approved', 1)
            ->where('is_active', 1)
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use (
                &$updated,
                &$invalid
            ) {
                foreach ($rows as $row) {
                    $result = $this->library
                        ->rebuildMetadata((int) $row->id);

                    if (!empty($result['updated'])) {
                        $updated++;
                    } else {
                        $invalid++;
                    }
                }
            });

        $this->info(
            "Đã cập nhật {$updated} bản ghi; "
            . "{$invalid} bản ghi cần rà soát."
        );

        return 0;
    }
}
