<?php

namespace App\Console\Commands;

use App\Services\ChatbotAnswerLibraryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildChatbotAnswerLibraryMetadata extends Command
{
    protected $signature = 'chatbot:rebuild-answer-library-metadata
        {--delete-invalid : Xóa các câu không còn đủ chất lượng}';

    protected $description = 'Phân tích lại intent, năm áp dụng và metadata của kho câu hỏi';

    public function handle(ChatbotAnswerLibraryService $service)
    {
        $deleteInvalid = (bool) $this->option('delete-invalid');
        $total = DB::table('chatbot_answer_library')->count();

        if ($total === 0) {
            $this->info('Kho câu hỏi đang trống.');

            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $updated = 0;
        $invalid = 0;
        $deleted = 0;

        DB::table('chatbot_answer_library')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (
                $service,
                $deleteInvalid,
                $bar,
                &$updated,
                &$invalid,
                &$deleted
            ) {
                foreach ($rows as $row) {
                    $result = $service->rebuildMetadata($row->id);

                    if ($result['valid']) {
                        $updated++;
                    } else {
                        $invalid++;

                        if ($deleteInvalid) {
                            DB::table('chatbot_answer_library')
                                ->where('id', $row->id)
                                ->delete();
                            $deleted++;
                        }
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Đã cập nhật metadata: ' . $updated);
        $this->warn('Không đạt chất lượng: ' . $invalid);

        if ($deleteInvalid) {
            $this->warn('Đã xóa: ' . $deleted);
        }

        return 0;
    }
}
