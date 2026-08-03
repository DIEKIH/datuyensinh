<?php

namespace App\Console\Commands;

use App\Services\ChatbotAnswerLibraryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillChatbotAnswerLibrary extends Command
{
    protected $signature = 'chatbot:backfill-answer-library
                            {--limit=0 : Số câu AI tối đa; 0 là toàn bộ}';

    protected $description =
        'Đưa hỏi đáp AI và nhân viên cũ vào kho câu trả lời chung';

    public function handle(ChatbotAnswerLibraryService $libraryService)
    {
        $aiResult = $this->backfillAiAnswers($libraryService);
        $staffResult = $this->backfillStaffAnswers($libraryService);

        $this->info(
            'AI: đã rà ' . $aiResult['processed']
            . ', lưu/cập nhật ' . $aiResult['stored'] . '.'
        );

        $this->info(
            'Nhân viên: đã rà ' . $staffResult['processed']
            . ', lưu/cập nhật ' . $staffResult['stored']
            . ', giữ trạng thái duyệt cũ ' . $staffResult['approved'] . '.'
        );

        return 0;
    }

    private function backfillAiAnswers(
        ChatbotAnswerLibraryService $libraryService
    ) {
        $limit = max(0, (int) $this->option('limit'));
        $processed = 0;
        $stored = 0;
        $lastId = 0;
        $batchSize = 300;

        do {
            $rows = DB::table('chatbot_messages as a')
                ->join(
                    'chatbot_messages as u',
                    'u.id',
                    '=',
                    'a.reply_to_id'
                )
                ->where('a.role', 'assistant')
                ->where('u.role', 'user')
                ->where('a.id', '>', $lastId)
                ->orderBy('a.id')
                ->limit($batchSize)
                ->select(
                    'a.id as assistant_message_id',
                    'a.session_id',
                    'a.content as answer',
                    'u.id as user_message_id',
                    'u.content as question'
                )
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = (int) $row->assistant_message_id;
                $processed++;

                $libraryId = $libraryService->storeAiCandidate(
                    $row->session_id,
                    $row->user_message_id,
                    $row->assistant_message_id,
                    $row->question,
                    $row->answer
                );

                if ($libraryId) {
                    $stored++;
                }

                if ($limit > 0 && $processed >= $limit) {
                    break 2;
                }
            }
        } while (true);

        return [
            'processed' => $processed,
            'stored' => $stored,
        ];
    }

    private function backfillStaffAnswers(
        ChatbotAnswerLibraryService $libraryService
    ) {
        $hasOldApprovalColumn = Schema::hasColumn(
            'chatbot_tickets',
            'is_public'
        );

        $columns = [
            'id',
            'session_id',
            'user_message_id',
            'question',
            'staff_answer',
            'answered_by',
        ];

        if ($hasOldApprovalColumn) {
            $columns[] = 'is_public';
        }

        $tickets = DB::table('chatbot_tickets')
            ->whereNotNull('staff_answer')
            ->where('staff_answer', '<>', '')
            ->whereIn('status', ['answered', 'closed'])
            ->orderBy('id')
            ->get($columns);

        $processed = 0;
        $stored = 0;
        $approvedCount = 0;

        foreach ($tickets as $ticket) {
            $processed++;
            $approved = $hasOldApprovalColumn
                && (int) $ticket->is_public === 1;

            $result = $libraryService->storeStaffAnswer(
                $ticket->id,
                $ticket->session_id,
                $ticket->user_message_id,
                $ticket->question,
                $ticket->staff_answer,
                $approved,
                $ticket->answered_by
            );

            if (!empty($result['id'])) {
                $stored++;
            }

            if (!empty($result['is_approved'])) {
                $approvedCount++;
            }
        }

        return [
            'processed' => $processed,
            'stored' => $stored,
            'approved' => $approvedCount,
        ];
    }
}
