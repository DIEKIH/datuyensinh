<?php

namespace App\Console\Commands;

use App\Services\ChatbotAnswerLibraryService;
use App\Services\ChatbotQueryAnalyzerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditChatbotAnswerLibrary extends Command
{
    protected $signature = 'chatbot:audit-answer-library';

    protected $description = 'Kiểm tra chất lượng, năm áp dụng và trùng lặp trong kho câu hỏi';

    public function handle(
        ChatbotAnswerLibraryService $library,
        ChatbotQueryAnalyzerService $analyzer
    ) {
        $rows = DB::table('chatbot_answer_library')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Kho câu hỏi đang trống.');

            return 0;
        }

        $issues = [];
        $hashGroups = [];

        foreach ($rows as $row) {
            $analysis = $analyzer->analyze($row->question);
            $reasons = [];

            if (!$library->canUseAsLibrary($row->question, $row->answer)) {
                $reasons[] = 'Không đạt chất lượng hoặc thiếu năm áp dụng';
            }

            if (
                $analysis['is_time_sensitive']
                && (!isset($row->admission_year) || !$row->admission_year)
            ) {
                $reasons[] = 'Nội dung theo thời gian nhưng thiếu admission_year';
            }

            if (
                isset($row->valid_until)
                && $row->valid_until
                && $row->valid_until < now()->toDateString()
            ) {
                $reasons[] = 'Đã hết hiệu lực';
            }

            $hash = (string) $row->question_hash;

            if (!isset($hashGroups[$hash])) {
                $hashGroups[$hash] = [];
            }

            $hashGroups[$hash][] = (int) $row->id;

            if (!empty($reasons)) {
                $issues[] = [
                    $row->id,
                    $row->source_type,
                    implode('; ', $reasons),
                    mb_substr($row->question, 0, 70, 'UTF-8'),
                ];
            }
        }

        foreach ($hashGroups as $ids) {
            if (count($ids) <= 1) {
                continue;
            }

            foreach ($ids as $id) {
                $issues[] = [
                    $id,
                    'duplicate',
                    'Trùng câu hỏi chuẩn hóa với ID: '
                        . implode(', ', $ids),
                    '',
                ];
            }
        }

        $this->info('Tổng số câu trong kho: ' . $rows->count());

        if (empty($issues)) {
            $this->info('Không phát hiện vấn đề rõ ràng.');

            return 0;
        }

        $this->table(
            ['ID', 'Nguồn', 'Vấn đề', 'Câu hỏi'],
            $issues
        );
        $this->warn('Số dòng cảnh báo: ' . count($issues));

        return 1;
    }
}
