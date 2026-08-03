<?php

namespace App\Services;

use App\Models\AdviseMessage;
use App\Models\AdviseTicket;

class AdviseConversationContextService
{
    private $analyzer;

    public function __construct(
        ChatbotQueryAnalyzerService $analyzer
    ) {
        $this->analyzer = $analyzer;
    }

    public function previousUserAnalysis($currentUserMessage)
    {
        if (!$currentUserMessage) {
            return null;
        }

        $previous = AdviseMessage::where(
            'session_id',
            $currentUserMessage->session_id
        )
            ->where('role', 'user')
            ->where('id', '<', $currentUserMessage->id)
            ->orderByDesc('id')
            ->first();

        if (!$previous) {
            return null;
        }

        return $this->analyzer->analyze($previous->content);
    }

    public function previousUserQuestion($currentUserMessage)
    {
        if (!$currentUserMessage) {
            return null;
        }

        $previous = AdviseMessage::where(
            'session_id',
            $currentUserMessage->session_id
        )
            ->where('role', 'user')
            ->where('id', '<', $currentUserMessage->id)
            ->orderByDesc('id')
            ->first();

        return $previous ? trim((string) $previous->content) : null;
    }

    public function previousAssistantMessage($currentUserMessage)
    {
        if (!$currentUserMessage) {
            return null;
        }

        return AdviseMessage::where(
            'session_id',
            $currentUserMessage->session_id
        )
            ->where('role', 'assistant')
            ->where('id', '<', $currentUserMessage->id)
            ->orderByDesc('id')
            ->first();
    }

    public function hasRecentAssistantQuestion($currentUserMessage)
    {
        $message = $this->previousAssistantMessage(
            $currentUserMessage
        );

        if (!$message) {
            return false;
        }

        $content = trim((string) $message->content);

        if ($content === '') {
            return false;
        }

        return strpos($content, '?') !== false
            || preg_match(
                '/bạn có muốn|bạn muốn|có cần|có muốn|'
                    . 'vui lòng cho biết|hãy cho biết/iu',
                $content
            ) === 1;
    }

    public function previousMeaningfulUserQuestion(
        $currentUserMessage,
        $ticketCommands = null
    ) {
        if (!$currentUserMessage) {
            return null;
        }

        $rows = AdviseMessage::where(
            'session_id',
            $currentUserMessage->session_id
        )
            ->where('role', 'user')
            ->where('id', '<', $currentUserMessage->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        foreach ($rows as $row) {
            $content = trim((string) $row->content);

            if ($content === '') {
                continue;
            }

            if (
                $ticketCommands
                && $ticketCommands->isRequest($content)
            ) {
                continue;
            }

            $normalized = $this->analyzer->normalize($content);

            if (preg_match(
                '/^(co|co a|da co|vang|dong y|ok|oke|okay|'
                    . 'duoc|khong|khong can|thoi|huy)$/',
                $normalized
            )) {
                continue;
            }

            return $content;
        }

        return null;
    }

    /**
     * Xây ngữ cảnh theo chủ đề, không lấy toàn bộ lịch sử một cách mù quáng.
     */
    public function buildResponseInput(
        $currentUserMessage,
        $fallbackContent
    ) {
        $fallbackContent = trim((string) $fallbackContent);

        if (!$currentUserMessage) {
            return [[
                'type' => 'message',
                'role' => 'user',
                'content' => $this->analyzer->redactSensitiveData(
                    $fallbackContent
                ),
            ]];
        }

        $maxMessages = (int) config(
            'chatbot.context.max_messages',
            10
        );
        $maxCharacters = (int) config(
            'chatbot.context.max_characters',
            12000
        );
        $maxMessages = max(4, min(20, $maxMessages));
        $maxCharacters = max(3000, min(30000, $maxCharacters));

        $currentAnalysis = $this->analyzer->analyze(
            $currentUserMessage->content
        );

        $rows = AdviseMessage::where(
            'session_id',
            $currentUserMessage->session_id
        )
            ->where('id', '<=', $currentUserMessage->id)
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $userRows = [];
        $assistantByReplyTo = [];

        foreach ($rows as $row) {
            if ($row->role === 'user') {
                $userRows[(int) $row->id] = $row;
                continue;
            }

            if ($row->role === 'assistant' && $row->reply_to_id) {
                $replyTo = (int) $row->reply_to_id;

                if (!isset($assistantByReplyTo[$replyTo])) {
                    $assistantByReplyTo[$replyTo] = [];
                }

                $assistantByReplyTo[$replyTo][] = $row;
            }
        }

        krsort($userRows);
        $selectedTurns = [];
        $selectedIds = [];
        $currentId = (int) $currentUserMessage->id;
        $isContextual = (bool) $currentAnalysis['is_context_dependent'];

        /*
         * Luôn giữ câu hiện tại và hai lượt gần nhất.
         * Đây là bộ nhớ hội thoại tối thiểu, giúp các câu như:
         * - "còn học phí?"
         * - "có"
         * - "ngành đó thì sao?"
         * hiểu đúng câu trước.
         */
        $recentOlderTurns = 0;

        foreach ($userRows as $userId => $userRow) {
            $isCurrent = $userId === $currentId;

            if ($isCurrent || $recentOlderTurns < 2) {
                $selectedTurns[] = $this->turnData(
                    $userRow,
                    isset($assistantByReplyTo[$userId])
                        ? $assistantByReplyTo[$userId]
                        : []
                );
                $selectedIds[$userId] = true;

                if (!$isCurrent) {
                    $recentOlderTurns++;
                }

                continue;
            }
        }

        /*
         * Bổ sung tối đa ba lượt cũ cùng chủ đề/thực thể.
         * Không kéo dữ liệu hồ sơ cá nhân sang câu hỏi công khai.
         */
        $relatedAdded = 0;

        foreach ($userRows as $userId => $userRow) {
            if (isset($selectedIds[$userId])) {
                continue;
            }

            $analysis = $this->analyzer->analyze(
                $userRow->content
            );

            if (
                $analysis['is_personal']
                && !$currentAnalysis['is_personal']
            ) {
                continue;
            }

            $sameTopic = $this->analyzer->hasIntentOverlap(
                $currentAnalysis['intents'],
                $analysis['intents']
            );
            $entityRelated = $this->entitiesOverlap(
                $currentAnalysis['entities'],
                $analysis['entities']
            );

            if ($sameTopic || $entityRelated || $isContextual) {
                $selectedTurns[] = $this->turnData(
                    $userRow,
                    isset($assistantByReplyTo[$userId])
                        ? $assistantByReplyTo[$userId]
                        : []
                );
                $selectedIds[$userId] = true;
                $relatedAdded++;

                if ($relatedAdded >= 3) {
                    break;
                }
            }
        }

        usort($selectedTurns, function ($left, $right) {
            return $left['user_order'] <=> $right['user_order'];
        });
        $events = [];

        foreach ($selectedTurns as $turn) {
            $events[] = [
                'role' => 'user',
                'content' => $turn['user'],
                'order' => $turn['user_order'],
            ];

            foreach ($turn['assistants'] as $assistant) {
                $events[] = [
                    'role' => 'assistant',
                    'content' => $assistant['content'],
                    'order' => $assistant['order'],
                ];
            }
        }

        /*
         * Chỉ đưa phản hồi nhân viên vào ngữ cảnh khi câu hiện tại liên quan
         * hồ sơ/ticket hoặc là câu phụ thuộc ngữ cảnh.
         */
        if (
            $currentAnalysis['is_personal']
            || $currentAnalysis['is_context_dependent']
            || in_array(
                'ticket_lookup',
                $currentAnalysis['intents'],
                true
            )
        ) {
            $ticket = AdviseTicket::where(
                'session_id',
                $currentUserMessage->session_id
            )
                ->whereNotNull('staff_answer')
                ->whereNotNull('answered_at')
                ->orderByDesc('answered_at')
                ->first();

            if ($ticket && trim((string) $ticket->staff_answer) !== '') {
                $events[] = [
                    'role' => 'assistant',
                    'content' => 'Nhân viên tư vấn đã phản hồi cho yêu cầu trước: '
                        . trim((string) $ticket->staff_answer),
                    'order' => 2000000000 + (int) $ticket->id,
                ];
            }
        }

        usort($events, function ($left, $right) {
            return $left['order'] <=> $right['order'];
        });

        /*
         * Chọn từ lượt mới nhất trở về trước để chắc chắn câu hiện tại
         * luôn nằm trong cửa sổ ngữ cảnh.
         */
        $selectedEvents = [];
        $characters = 0;

        for ($index = count($events) - 1; $index >= 0; $index--) {
            $event = $events[$index];
            $content = trim((string) $event['content']);

            if ($content === '') {
                continue;
            }

            if ($this->isTicketSystemMessage($content)) {
                if (
                    !$currentAnalysis['is_personal']
                    && !$currentAnalysis['is_context_dependent']
                ) {
                    continue;
                }
            }

            $content = $this->analyzer->redactSensitiveData($content);
            $length = mb_strlen($content, 'UTF-8');

            if (
                !empty($selectedEvents)
                && ($characters + $length) > $maxCharacters
            ) {
                break;
            }

            $selectedEvents[] = [
                'type' => 'message',
                'role' => $event['role'],
                'content' => $content,
            ];
            $characters += $length;

            if (count($selectedEvents) >= $maxMessages) {
                break;
            }
        }

        $input = array_reverse($selectedEvents);

        if (empty($input)) {
            $input[] = [
                'type' => 'message',
                'role' => 'user',
                'content' => $this->analyzer->redactSensitiveData(
                    $fallbackContent
                ),
            ];
        }

        return $input;
    }

    private function turnData($userRow, array $assistantRows)
    {
        usort($assistantRows, function ($left, $right) {
            return (int) $left->id <=> (int) $right->id;
        });

        $assistants = [];

        foreach ($assistantRows as $assistant) {
            $content = trim((string) $assistant->content);

            if ($content === '') {
                continue;
            }

            $assistants[] = [
                'content' => $content,
                'order' => (int) $assistant->id,
            ];
        }

        return [
            'user' => trim((string) $userRow->content),
            'user_order' => (int) $userRow->id,
            'assistants' => $assistants,
        ];
    }

    private function entitiesOverlap(array $left, array $right)
    {
        foreach (['years', 'methods', 'majors', 'subject_combinations'] as $key) {
            $leftValues = isset($left[$key]) ? $left[$key] : [];
            $rightValues = isset($right[$key]) ? $right[$key] : [];

            if (!empty(array_intersect($leftValues, $rightValues))) {
                return true;
            }
        }

        return false;
    }

    private function isTicketSystemMessage($content)
    {
        return preg_match(
            '/(ma tra cuu cua ban|nhan vien tu van da phan hoi|'
            . 'nha truong da ghi nhan cau hoi|tao ticket)/iu',
            $this->analyzer->normalize($content)
        ) === 1;
    }
}
