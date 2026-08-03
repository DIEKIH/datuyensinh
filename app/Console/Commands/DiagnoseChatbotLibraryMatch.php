<?php

namespace App\Console\Commands;

use App\Services\ChatbotAnswerLibraryService;
use Illuminate\Console\Command;

class DiagnoseChatbotLibraryMatch extends Command
{
    protected $signature =
        'chatbot:library-diagnose '
        . '{question : Câu hỏi cần kiểm tra}';

    protected $description =
        'Chẩn đoán toàn bộ pipeline nhận diện kho chatbot';

    private $library;

    public function __construct(
        ChatbotAnswerLibraryService $library
    ) {
        parent::__construct();
        $this->library = $library;
    }

    public function handle()
    {
        $result = $this->library
            ->diagnoseApprovedAnswer(
                (string) $this->argument('question')
            );

        $this->line(json_encode(
            $result,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRETTY_PRINT
        ));

        return 0;
    }
}
