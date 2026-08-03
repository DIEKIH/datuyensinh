<?php

namespace App\Console\Commands;

use App\Services\ChatbotAnswerLibraryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildChatbotLibraryVectors extends Command
{
    protected $signature =
        'chatbot:library-vectors {--chunk=50}';

    protected $description =
        'Tạo lại semantic vector cho kho câu trả lời chatbot';

    private $library;

    public function __construct(
        ChatbotAnswerLibraryService $library
    ) {
        parent::__construct();
        $this->library = $library;
    }

    public function handle()
    {
        $chunk = max(10, (int) $this->option('chunk'));
        $success = 0;
        $failed = 0;

        DB::table('chatbot_answer_library')
            ->where('is_approved', 1)
            ->where('is_active', 1)
            ->orderBy('id')
            ->chunkById(
                $chunk,
                function ($rows) use (
                    &$success,
                    &$failed
                ) {
                    foreach ($rows as $row) {
                        if (
                            $this->library->rebuildVector(
                                (int) $row->id
                            )
                        ) {
                            $success++;
                        } else {
                            $failed++;
                        }
                    }

                    $this->line(
                        "Đã tạo: {$success}; "
                        . "chưa tạo: {$failed}"
                    );
                }
            );

        $this->info(
            "Hoàn tất. Thành công: {$success}; "
            . "chưa tạo: {$failed}."
        );

        return 0;
    }
}
