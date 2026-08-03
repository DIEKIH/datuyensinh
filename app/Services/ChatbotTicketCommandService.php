<?php

namespace App\Services;

use App\Models\AdviseMessage;
use Illuminate\Support\Str;

class ChatbotTicketCommandService
{
    /**
     * Phân tích yêu cầu chủ động tạo ticket.
     *
     * Đây là lệnh điều khiển, phải được xử lý trước:
     * - kho câu trả lời;
     * - phân tích câu hỏi;
     * - File Search/OpenAI.
     */
    public function analyze($text)
    {
        $raw = trim((string) $text);
        $normalized = $this->normalize($raw);

        if ($raw === '') {
            return $this->emptyResult();
        }

        $requestPatterns = [
            '/^(?:hay\s+)?tao\s+(?:giup\s+(?:toi|minh|em)\s+)?ticket\b/i',
            '/^tao\s+yeu\s+cau\s+tu\s+van\b/i',
            '/^chuyen\s+(?:cau\s+(?:hoi\s+)?nay\s+)?'
                . '(?:cho|den)\s+(?:nhan\s+vien|tu\s+van\s+vien)\b/i',
            '/^gui\s+(?:cau\s+hoi\s+)?(?:nay\s+)?'
                . '(?:cho|den)\s+(?:nhan\s+vien|tu\s+van\s+vien)\b/i',
            '/^nho\s+(?:nhan\s+vien|tu\s+van\s+vien)\s+'
                . '(?:kiem\s+tra|tu\s+van|ho\s+tro)\b/i',
            '/^toi\s+muon\s+gap\s+(?:nhan\s+vien|tu\s+van\s+vien)\b/i',
            '/^cho\s+(?:toi|minh|em)\s+gap\s+'
                . '(?:nhan\s+vien|tu\s+van\s+vien)\b/i',
        ];

        $isRequest = false;

        foreach ($requestPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                $isRequest = true;
                break;
            }
        }

        if (!$isRequest) {
            return $this->emptyResult($raw, $normalized);
        }

        $payload = $this->extractPayload($raw);

        return [
            'is_request' => true,
            'raw' => $raw,
            'normalized' => $normalized,
            'payload' => $payload,
            'has_payload' => $payload !== '',
            'uses_previous_question' => $payload === '',
        ];
    }

    public function isRequest($text)
    {
        $result = $this->analyze($text);

        return (bool) $result['is_request'];
    }

    /**
     * Lấy nội dung sau lệnh tạo ticket nhưng giữ nguyên tiếng Việt,
     * tên riêng, mã hồ sơ và dữ liệu người dùng đã nhập.
     */
    public function extractPayload($text)
    {
        $text = trim((string) $text);

        $patterns = [
            '/^\s*(?:hãy\s+)?tạo\s+(?:giúp\s+(?:tôi|mình|em)\s+)?'
                . 'ticket\s*(?:với|cho|về)?\s*'
                . '(?:câu\s+hỏi|nội\s+dung)?\s*[:\-]\s*(.+)$/iu',
            '/^\s*tạo\s+ticket\s+(?:tư\s+vấn\s+)?'
                . '(?:về|cho)\s+(.+)$/iu',
            '/^\s*tạo\s+(?:yêu\s+cầu\s+)?tư\s+vấn\s+'
                . '(?:về|cho)\s+(.+)$/iu',
            '/^\s*chuyển\s+(.+?)\s+(?:cho|đến)\s+'
                . '(?:nhân\s+viên|tư\s+vấn\s+viên).*$/iu',
            '/^\s*gửi\s+(.+?)\s+(?:cho|đến)\s+'
                . '(?:nhân\s+viên|tư\s+vấn\s+viên).*$/iu',
            '/^\s*nhờ\s+(?:nhân\s+viên|tư\s+vấn\s+viên)\s+'
                . '(?:kiểm\s+tra|tư\s+vấn|hỗ\s+trợ)\s*'
                . '(?:về|giúp)?\s*[:\-]?\s*(.+)$/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $payload = trim((string) $matches[1]);
                $payload = preg_replace(
                    '/^(?:câu\s+hỏi|nội\s+dung)\s*[:\-]\s*/iu',
                    '',
                    $payload
                );
                $payload = trim($payload);

                if (!$this->isOnlyReferencePhrase($payload)) {
                    return $payload;
                }
            }
        }

        return '';
    }

    public function resolveQuestion(
        array $command,
        $currentUserMessage,
        $fallbackPreviousQuestion = null
    ) {
        if (!empty($command['payload'])) {
            return trim((string) $command['payload']);
        }

        $fallbackPreviousQuestion = trim(
            (string) $fallbackPreviousQuestion
        );

        if ($fallbackPreviousQuestion !== '') {
            return $fallbackPreviousQuestion;
        }

        if (!$currentUserMessage) {
            return '';
        }

        $rows = AdviseMessage::where(
            'session_id',
            $currentUserMessage->session_id
        )
            ->where('role', 'user')
            ->where('id', '<', $currentUserMessage->id)
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        foreach ($rows as $row) {
            $candidate = trim((string) $row->content);

            if ($candidate === '') {
                continue;
            }

            if ($this->isRequest($candidate)) {
                continue;
            }

            if ($this->isSimpleConfirmation($candidate)) {
                continue;
            }

            return $candidate;
        }

        return '';
    }

    public function buildCreateMarker($question, $reason = null)
    {
        $question = trim((string) $question);
        $reason = trim((string) $reason);

        if ($reason === '') {
            $reason =
                'Người dùng chủ động yêu cầu tạo ticket tư vấn.';
        }

        return "[CREATE_TICKET]\n"
            . "Câu hỏi cần chuyển: {$question}\n"
            . "Lý do: {$reason}\n"
            . "[/CREATE_TICKET]";
    }

    public function clarificationMessage()
    {
        return 'Bạn muốn nhân viên tư vấn CTUT hỗ trợ nội dung gì? '
            . 'Bạn có thể nhập theo mẫu: '
            . '**Tạo ticket với câu hỏi: [nội dung cần hỗ trợ]**.';
    }

    private function normalize($text)
    {
        $text = mb_strtolower(trim((string) $text), 'UTF-8');
        $text = Str::ascii($text);
        $text = preg_replace('/[^a-z0-9\s:,\-]+/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function isOnlyReferencePhrase($text)
    {
        $normalized = $this->normalize($text);

        return in_array(
            $normalized,
            [
                '',
                'cau nay',
                'cau hoi nay',
                'noi dung nay',
                'van de nay',
                'chuyen nay',
                'giup toi',
                'giup minh',
                'giup em',
            ],
            true
        );
    }

    private function isSimpleConfirmation($text)
    {
        $normalized = $this->normalize($text);

        return preg_match(
            '/^(co|co a|da co|vang|dong y|ok|oke|okay|'
                . 'duoc|khong|khong can|thoi|huy)$/',
            $normalized
        ) === 1;
    }

    private function emptyResult($raw = '', $normalized = '')
    {
        return [
            'is_request' => false,
            'raw' => $raw,
            'normalized' => $normalized,
            'payload' => '',
            'has_payload' => false,
            'uses_previous_question' => false,
        ];
    }
}
