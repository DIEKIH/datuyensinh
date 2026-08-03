<?php

namespace App\Services;

use Illuminate\Support\Str;

class ChatbotQueryAnalyzerService
{
    /**
     * Phân tích tổng quát.
     *
     * Không có danh sách chủ đề, tên ngành, phương thức, tổ hợp,
     * từ viết tắt nghiệp vụ hoặc câu hỏi mẫu.
     */
    public function analyze($text)
    {
        $raw = $this->cleanText($text);
        $normalized = $this->normalize($raw);
        $tokens = $this->significantTokens($normalized);
        $intents = $this->detectGenericTopics($tokens);
        $entities = $this->extractGenericEntities(
            $raw,
            $normalized
        );

        $wordCount = count(array_values(array_filter(
            preg_split('/\s+/', $normalized)
        )));

        $questionClauseCount = $this->questionClauseCount($raw);
        $isPersonal = $this->isPersonalOrTransactional(
            $raw,
            $normalized
        );
        $isTicketCode = preg_match(
            '/^TV\d{8}-[A-Z0-9]{6}$/i',
            trim($raw)
        ) === 1;
        $isContextDependent = $this->isContextDependent(
            $normalized
        );
        $isComparison = preg_match(
            '/\b(so voi|khac nhau|hon|kem hon|'
                . 'tot hon|thap hon|cao hon|giua)\b/i',
            $normalized
        ) === 1;
        $isQuestion = $this->isQuestion(
            $raw,
            $normalized
        );
        $isYesNoQuestion = $this->isYesNoQuestion(
            $raw,
            $normalized
        );
        $isNegation = $this->hasSemanticNegation(
            $normalized,
            $isYesNoQuestion
        );
        $polarity = $isNegation
            ? 'negative'
            : 'neutral';
        $isConditional = preg_match(
            '/\b(neu|gia su|truong hop|khi)\b/i',
            $normalized
        ) === 1;
        $isGibberish = $this->isGibberish(
            $raw,
            $normalized
        );
        $isShort = $wordCount < 2
            || count($tokens) === 0
            || mb_strlen($normalized, 'UTF-8') < 4;
        $isMultiIntent = $questionClauseCount > 1;
        $hasExplicitYear = !empty($entities['years']);
        $usesRelativeTime = preg_match(
            '/\b(hien nay|bay gio|moi nhat|sap toi|'
                . 'hom nay|ngay mai|nam nay|nam sau|nam truoc)\b/i',
            $normalized
        ) === 1;
        $isTimeSensitive = $hasExplicitYear
            || $usesRelativeTime;

        $resolvedYear = null;

        if ($hasExplicitYear) {
            $resolvedYear = (int) $entities['years'][0];
        } elseif ($usesRelativeTime) {
            $resolvedYear = (int) now()->year;
        }

        $primaryIntent = !empty($intents)
            ? $intents[0]
            : 'generic';

        $intentSignature = !empty($intents)
            ? implode('|', $intents)
            : 'generic';

        /*
         * Câu hỏi kiểu “Có ... không?” không phải phủ định.
         * Chỉ phủ định thật mới buộc khớp chặt.
         */
        $requiresExactMatch = $isPersonal
            || $isContextDependent
            || $isComparison
            || (
                $isNegation
                && !$isQuestion
            )
            || $isConditional
            || $isMultiIntent
            || $isShort
            || $isGibberish;

        $allowApproximateMatch = !$requiresExactMatch
            && !$isTicketCode
            && count($tokens) >= 2;

        return [
            'raw' => $raw,
            'normalized' => $normalized,
            'tokens' => $tokens,
            'intents' => $intents,
            'primary_intent' => $primaryIntent,
            'intent_signature' => $intentSignature,
            'entities' => $entities,
            'resolved_year' => $resolvedYear,
            'question_clause_count' => $questionClauseCount,
            'word_count' => $wordCount,
            'is_personal' => $isPersonal,
            'is_ticket_code' => $isTicketCode,
            'is_context_dependent' => $isContextDependent,
            'is_comparison' => $isComparison,
            'is_question' => $isQuestion,
            'is_yes_no_question' => $isYesNoQuestion,
            'is_negation' => $isNegation,
            'polarity' => $polarity,
            'is_conditional' => $isConditional,
            'is_multi_intent' => $isMultiIntent,
            'is_time_sensitive' => $isTimeSensitive,
            'has_explicit_year' => $hasExplicitYear,
            'uses_relative_time' => $usesRelativeTime,

            /*
             * Giữ các khóa này để tương thích code cũ.
             * Không hard-code lời chào, cảm ơn, chửi tục hay chủ đề.
             */
            'is_greeting' => false,
            'is_thanks' => false,
            'is_goodbye' => false,
            'is_abusive' => false,

            'is_gibberish' => $isGibberish,
            'is_short' => $isShort,
            'requires_exact_match' => $requiresExactMatch,
            'allow_approximate_match' => $allowApproximateMatch,
            'question_hash' => hash(
                'sha256',
                $normalized
            ),
        ];
    }

    /**
     * Chỉ xử lý các tình huống kỹ thuật chung.
     * Không trả câu mẫu riêng cho từng chủ đề.
     */
    public function localResponse(
        array $analysis,
        array $previousAnalysis = null,
        array $conversationHints = []
    ) {
        if ($analysis['raw'] === '') {
            return 'Bạn vui lòng nhập nội dung cần hỏi.';
        }

        if ($analysis['is_ticket_code']) {
            return 'Chuỗi bạn vừa nhập là mã tra cứu tư vấn. '
                . 'Bạn vui lòng nhập mã đó vào mục tra cứu phản hồi.';
        }

        if ($analysis['is_gibberish']) {
            return 'Mình chưa hiểu nội dung vừa nhập. '
                . 'Bạn vui lòng viết lại câu hỏi rõ ràng hơn.';
        }

        if ($analysis['is_personal']) {
            return 'Nội dung này có dấu hiệu liên quan dữ liệu cá nhân '
                . 'hoặc trạng thái giao dịch. Bạn có muốn chuyển câu hỏi '
                . 'đến nhân viên tư vấn để kiểm tra không?';
        }

        if (
            $analysis['is_context_dependent']
            && $previousAnalysis === null
        ) {
            return 'Bạn vui lòng viết lại đầy đủ đối tượng '
                . 'và nội dung cần hỏi.';
        }

        $hasRecentAssistantQuestion = !empty(
            $conversationHints['has_recent_assistant_question']
        );

        if (
            $analysis['is_short']
            && !$hasRecentAssistantQuestion
        ) {
            return 'Bạn vui lòng nói rõ hơn nội dung cần hỏi.';
        }

        return null;
    }

    /**
     * Admin được quyền lưu nếu không có lỗi cứng.
     *
     * Không kiểm tra câu trả lời bằng danh sách chủ đề cụ thể.
     */
    public function reviewForLibrary($question, $answer)
    {
        $question = $this->cleanText((string) $question);
        $answer = $this->cleanText(
            strip_tags((string) $answer)
        );

        $blockingReasons = [];
        $warnings = [];

        if ($question === '') {
            $blockingReasons[] =
                'Câu hỏi chuẩn đang để trống.';
        }

        if ($answer === '') {
            $blockingReasons[] =
                'Câu trả lời dùng lại đang để trống.';
        }

        $questionAnalysis = $this->analyze($question);
        $answerAnalysis = $this->analyze($answer);

        if ($questionAnalysis['is_personal']) {
            $blockingReasons[] =
                'Câu hỏi có dấu hiệu chứa dữ liệu cá nhân.';
        }

        if ($questionAnalysis['is_ticket_code']) {
            $blockingReasons[] =
                'Mã tra cứu tư vấn không được lưu dùng chung.';
        }

        $combined = $question . ' ' . $answer;

        if (preg_match(
            '/\[CREATE_TICKET\]|\[CAN_HANDOFF\]|'
                . 'TV\d{8}-[A-Z0-9]{6}/iu',
            $combined
        )) {
            $blockingReasons[] =
                'Nội dung thuộc luồng ticket hoặc mã tra cứu.';
        }

        if ($questionAnalysis['is_gibberish']) {
            $warnings[] =
                'Câu hỏi có vẻ chưa rõ nghĩa.';
        }

        if ($questionAnalysis['is_short']) {
            $warnings[] =
                'Câu hỏi khá ngắn; nên chỉnh rõ hơn khi cần.';
        }

        if ($questionAnalysis['is_context_dependent']) {
            $warnings[] =
                'Câu hỏi đang phụ thuộc ngữ cảnh trước đó.';
        }

        if (
            $questionAnalysis['uses_relative_time']
            && empty($questionAnalysis['entities']['years'])
            && empty($answerAnalysis['entities']['years'])
        ) {
            $warnings[] =
                'Nội dung dùng thời gian tương đối; nên ghi năm rõ ràng.';
        }

        if (!$this->isAnswerQualityAcceptable($answer)) {
            $warnings[] =
                'Câu trả lời còn quá ngắn hoặc có dấu hiệu là dữ liệu thử.';
        }

        $blockingReasons = array_values(array_unique(
            $blockingReasons
        ));
        $warnings = array_values(array_unique($warnings));

        $canSave = empty($blockingReasons);
        $reuseReady = $canSave
            && $this->isEligibleForLibrary(
                $question,
                $answer
            );

        return [
            'can_save' => $canSave,
            'hard_block' => !$canSave,
            'blocking_reasons' => $blockingReasons,
            'warnings' => $warnings,
            'reuse_ready' => $reuseReady,
            'resolved_year' =>
                $questionAnalysis['resolved_year'],
            'primary_intent' =>
                $questionAnalysis['primary_intent'],
            'intent_signature' =>
                $questionAnalysis['intent_signature'],
        ];
    }

    public function isEligibleForLibrary($question, $answer)
    {
        $analysis = $this->analyze($question);

        if (
            $analysis['is_personal']
            || $analysis['is_ticket_code']
            || $analysis['is_gibberish']
        ) {
            return false;
        }

        return $this->isAnswerQualityAcceptable($answer);
    }

    public function isAnswerQualityAcceptable($answer)
    {
        $answer = $this->cleanText(
            strip_tags((string) $answer)
        );

        if (mb_strlen($answer, 'UTF-8') < 12) {
            return false;
        }

        $normalized = $this->normalize($answer);
        $tokens = $this->significantTokens($normalized);

        if (count($tokens) < 2) {
            return false;
        }

        if (preg_match(
            '/([a-z])\1{5,}/i',
            $normalized
        )) {
            return false;
        }

        if (preg_match(
            '/\b(?=[a-z0-9]*[a-z])'
                . '(?=[a-z0-9]*\d)[a-z0-9]{12,}\b/i',
            $normalized
        )) {
            return false;
        }

        if (preg_match(
            '/TV\d{8}-[A-Z0-9]{6}/i',
            $answer
        )) {
            return false;
        }

        return true;
    }

    /**
     * Không đoán câu trả lời đúng/sai theo chủ đề.
     * Admin là người duyệt nội dung; hệ thống chỉ kiểm tra chất lượng tối thiểu.
     */
    public function answerSupportsQuestion($question, $answer)
    {
        return $this->cleanText($question) !== ''
            && $this->isAnswerQualityAcceptable($answer);
    }

    /**
     * Chuẩn hóa thuần hình thức, không chứa trường hợp nghiệp vụ.
     */
    public function normalize($text)
    {
        $text = $this->cleanText($text);

        if ($text === '') {
            return '';
        }

        $text = mb_strtolower($text, 'UTF-8');
        $text = Str::ascii($text);

        $text = preg_replace(
            '/([a-z])([0-9])/i',
            '$1 $2',
            $text
        );
        $text = preg_replace(
            '/([0-9])([a-z])/i',
            '$1 $2',
            $text
        );
        $text = preg_replace(
            '/[^a-z0-9\s]+/',
            ' ',
            $text
        );
        $text = preg_replace(
            '/([a-z])\1{2,}/i',
            '$1$1',
            $text
        );
        $text = trim(preg_replace(
            '/\s+/',
            ' ',
            $text
        ));

        if ($text === '') {
            return '';
        }

        $tokens = preg_split('/\s+/', $text);
        $result = [];
        $previous = null;

        foreach ($tokens as $token) {
            if ($token === '' || $token === $previous) {
                continue;
            }

            $result[] = $token;
            $previous = $token;
        }

        return implode(' ', $result);
    }

    /**
     * Chỉ loại các từ chức năng thông dụng.
     * Không chứa tên chủ đề, ngành hoặc nghiệp vụ.
     */
    public function significantTokens($normalizedText)
    {
        $stopWords = [
            'co', 'khong', 'va', 'hoac', 'cho', 'cua',
            'toi', 'minh', 'ban', 'em', 'anh', 'chi',
            'la', 'duoc', 've', 'tai', 'trong', 'ngoai',
            'mot', 'nhung', 'cac', 'nay', 'do', 'kia',
            'gi', 'nao', 'the', 'vay', 'thi', 'a',
            'nhe', 'nha', 'oi', 'voi', 'xin',
            'truong', 'sinh', 'vien', 'hoc', 'nganh',
            'nam', 'can', 'hoi', 'biet', 'giup',
        ];

        $tokens = array_values(array_filter(
            preg_split(
                '/\s+/',
                trim((string) $normalizedText)
            )
        ));

        $tokens = array_values(array_filter(
            $tokens,
            function ($token) use ($stopWords) {
                return strlen($token) >= 2
                    && !in_array(
                        $token,
                        $stopWords,
                        true
                    );
            }
        ));

        return array_values(array_unique($tokens));
    }

    public function redactSensitiveData($text)
    {
        $text = (string) $text;
        $text = preg_replace(
            '/\b\d{9,12}\b/',
            '[SỐ ĐỊNH DANH]',
            $text
        );
        $text = preg_replace(
            '/[a-zA-Z0-9._%+\-]+'
                . '@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
            '[EMAIL]',
            $text
        );
        $text = preg_replace(
            '/\b(?:0|\+84)\d{8,10}\b/',
            '[SỐ ĐIỆN THOẠI]',
            $text
        );
        $text = preg_replace(
            '/\bTV\d{8}-[A-Z0-9]{6}\b/i',
            '[MÃ TƯ VẤN]',
            $text
        );

        return $text;
    }

    /**
     * So ngữ cảnh bằng các từ chủ đề được sinh động từ câu,
     * không bằng danh sách intent cố định.
     */
    public function hasIntentOverlap(array $left, array $right)
    {
        if (empty($left) || empty($right)) {
            return false;
        }

        return !empty(array_intersect($left, $right));
    }

    /**
     * Sinh nhãn chủ đề từ chính token của câu hỏi.
     *
     * Ví dụ một chủ đề mới chưa từng có trong code vẫn tạo được
     * dấu vân tay mà không cần thêm regex.
     */
    private function detectGenericTopics(array $tokens)
    {
        $tokens = array_values(array_slice($tokens, 0, 10));
        $topics = [];

        /*
         * Tạo nhãn đơn từ và cặp từ liền nhau.
         * Cặp từ giúp phân biệt chủ đề mà không cần biết trước
         * chủ đề đó là gì.
         */
        foreach ($tokens as $index => $token) {
            $topics[] = 'term:' . $token;

            if (isset($tokens[$index + 1])) {
                $topics[] = 'gram:'
                    . $token
                    . '_'
                    . $tokens[$index + 1];
            }
        }

        return array_values(array_unique($topics));
    }

    /**
     * Chỉ trích xuất thực thể có cấu trúc chung.
     *
     * Không có danh sách tên ngành, phương thức hoặc tổ hợp.
     */
    private function extractGenericEntities(
        $raw,
        $normalized
    ) {
        preg_match_all(
            '/\b(19\d{2}|20\d{2}|21\d{2})\b/',
            $normalized,
            $yearMatches
        );

        $years = array_values(array_unique(array_map(
            'intval',
            isset($yearMatches[1])
                ? $yearMatches[1]
                : []
        )));

        preg_match_all(
            '/\b[A-Za-z]{1,8}[\-_]?\d{1,12}'
                . '(?:[\-_][A-Za-z0-9]{1,12})?\b/',
            (string) $raw,
            $codeMatches
        );

        $codes = array_values(array_unique(array_map(
            'strtoupper',
            isset($codeMatches[0])
                ? $codeMatches[0]
                : []
        )));

        preg_match_all(
            '/\b\d+(?:[\.,]\d+)?\b/',
            $normalized,
            $numberMatches
        );

        $numbers = array_values(array_unique(
            isset($numberMatches[0])
                ? $numberMatches[0]
                : []
        ));

        $yearStrings = array_map('strval', $years);

        $numbers = array_values(array_filter(
            $numbers,
            function ($number) use ($yearStrings) {
                return !in_array(
                    (string) $number,
                    $yearStrings,
                    true
                );
            }
        ));

        return [
            'years' => $years,
            'codes' => $codes,
            'numbers' => $numbers,

            /*
             * Giữ khóa cũ để không làm hỏng dữ liệu/API hiện tại.
             * Không còn phân tích bằng danh sách cụ thể.
             */
            'methods' => [],
            'majors' => [],
            'subject_combinations' => [],
        ];
    }

    private function isPersonalOrTransactional(
        $raw,
        $normalized
    ) {
        if (preg_match('/\b\d{9,12}\b/', $raw)) {
            return true;
        }

        if (preg_match(
            '/[a-zA-Z0-9._%+\-]+'
                . '@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
            $raw
        )) {
            return true;
        }

        if (preg_match(
            '/\b(?:0|\+84)\d{8,10}\b/',
            $raw
        )) {
            return true;
        }

        /*
         * Chỉ nhận diện cấu trúc giao dịch/cá nhân chung.
         * Không gắn với chủ đề tuyển sinh cụ thể.
         */
        return preg_match(
            '/\b(cua toi|cua em|tai khoan|trang thai|'
                . 'da duyet|da thanh toan|ma ho so|'
                . 'ma giao dich|ket qua cua)\b/i',
            $normalized
        ) === 1;
    }

    /**
     * Nhận diện hình thức câu hỏi tổng quát.
     */
    private function isQuestion($raw, $normalized)
    {
        if (preg_match('/[?？]\s*$/u', (string) $raw)) {
            return true;
        }

        return preg_match(
            '/^(co|duoc|lieu|phai|can|nen)\b.*'
                . '\b(khong|chua)$/i',
            $normalized
        ) === 1
            || preg_match(
                '/\b(gi|nao|bao nhieu|khi nao|o dau|'
                    . 'vi sao|tai sao)$/i',
                $normalized
            ) === 1;
    }

    /**
     * Phân biệt “không” cuối câu hỏi với phủ định.
     *
     * Ví dụ:
     * - “Có chỗ sạc xe không?” => câu hỏi Có/Không.
     * - “Trường không có chỗ sạc xe.” => phủ định.
     */
    private function isYesNoQuestion($raw, $normalized)
    {
        $endsWithQuestionMark = preg_match(
            '/[?？]\s*$/u',
            (string) $raw
        ) === 1;

        $endsWithQuestionParticle = preg_match(
            '/\b(khong|chua)\s*$/i',
            $normalized
        ) === 1;

        if (
            $endsWithQuestionMark
            && $endsWithQuestionParticle
        ) {
            return true;
        }

        return preg_match(
            '/^(co|duoc|lieu|phai|can|nen)\b.+'
                . '\b(khong|chua)$/i',
            $normalized
        ) === 1;
    }

    /**
     * Chỉ nhận diện phủ định có nội dung.
     * Không xem trợ từ “không?” ở cuối câu là phủ định.
     */
    private function hasSemanticNegation(
        $normalized,
        $isYesNoQuestion
    ) {
        $working = trim((string) $normalized);

        if ($isYesNoQuestion) {
            $working = preg_replace(
                '/\b(khong|chua)\s*$/i',
                '',
                $working
            );
            $working = trim($working);
        }

        return preg_match(
            '/\b(khong|chua|chang)\s+'
                . '(co|duoc|phai|can|muon|the|con|dung|ro|biet)\b/i',
            $working
        ) === 1;
    }

    private function isContextDependent($normalized)
    {
        if ($normalized === '') {
            return false;
        }

        return preg_match(
            '/^(cai do|cai nay|cai kia|do|nay|kia|'
                . 'the nao|con sao|con lai|the con|'
                . 'vay con|roi sao|bao nhieu|khi nao)$/i',
            $normalized
        ) === 1;
    }

    private function isGibberish($raw, $normalized)
    {
        if ($normalized === '') {
            return false;
        }

        if (preg_match(
            '/\b(?=[a-z0-9]*[a-z])'
                . '(?=[a-z0-9]*\d)[a-z0-9]{12,}\b/i',
            $normalized
        )) {
            return true;
        }

        if (preg_match(
            '/([a-z])\1{5,}/i',
            $normalized
        )) {
            return true;
        }

        $letters = preg_replace(
            '/[^a-z]/',
            '',
            $normalized
        );

        if (strlen($letters) >= 12) {
            $vowels = preg_replace(
                '/[^aeiouy]/',
                '',
                $letters
            );
            $ratio = strlen($vowels)
                / max(1, strlen($letters));

            if ($ratio < 0.10) {
                return true;
            }
        }

        return false;
    }

    private function questionClauseCount($raw)
    {
        $count = preg_match_all(
            '/[?？]+/u',
            (string) $raw,
            $matches
        );
        $count = max(1, (int) $count);

        if (preg_match(
            '/\b(và|hoặc|đồng thời|ngoài ra)\b/iu',
            (string) $raw
        )) {
            $count++;
        }

        return $count;
    }

    private function cleanText($text)
    {
        $text = trim(strip_tags((string) $text));
        $text = preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
            '',
            $text
        );

        return trim(preg_replace(
            '/\s+/u',
            ' ',
            $text
        ));
    }
}
