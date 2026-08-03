<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ChatbotAnswerLibraryService
{
    private const TABLE = 'chatbot_answer_library';

    private $analyzer;
    private $ticketCommands;
    private $matcher;
    private $embeddings;
    private $vectors;
    private static $metadataColumns = null;

    public function __construct(
        ChatbotQueryAnalyzerService $analyzer,
        ChatbotTicketCommandService $ticketCommands,
        ChatbotQuestionMatcherService $matcher,
        ChatbotSemanticEmbeddingService $embeddings,
        ChatbotAnswerLibraryVectorService $vectors
    ) {
        $this->analyzer = $analyzer;
        $this->ticketCommands = $ticketCommands;
        $this->matcher = $matcher;
        $this->embeddings = $embeddings;
        $this->vectors = $vectors;
    }

    /**
     * Giữ tương thích với code cũ: câu AI không tự vào kho.
     */
    public function storeAiCandidate(
        $sessionId,
        $userMessageId,
        $assistantMessageId,
        $question,
        $answer
    ) {
        return null;
    }

    public function storeAiAnswer(
        $sessionId,
        $userMessageId,
        $assistantMessageId,
        $question,
        $answer,
        $adminId
    ) {
        $assistantMessageId = (int) $assistantMessageId;
        $adminId = (int) $adminId;

        return $this->storeEntry(
            'ai',
            $assistantMessageId,
            [
                'session_id' => $sessionId ? (int) $sessionId : null,
                'user_message_id' => $userMessageId
                    ? (int) $userMessageId
                    : null,
                'assistant_message_id' => $assistantMessageId,
                'ticket_id' => null,
            ],
            $question,
            $answer,
            $adminId
        );
    }

    public function removeAiAnswer($assistantMessageId)
    {
        $record = DB::table(self::TABLE)
            ->where('source_type', 'ai')
            ->where('source_id', (int) $assistantMessageId)
            ->first();

        DB::table(self::TABLE)
            ->where('source_type', 'ai')
            ->where('source_id', (int) $assistantMessageId)
            ->delete();

        if ($record) {
            $this->vectors->delete((int) $record->id);
        }

        return [
            'id' => null,
            'is_in_library' => false,
        ];
    }

    public function storeStaffAnswer(
        $ticketId,
        $sessionId,
        $userMessageId,
        $question,
        $answer,
        $useAsSample,
        $adminId
    ) {
        $ticketId = (int) $ticketId;
        $useAsSample = (bool) $useAsSample;

        if (!$useAsSample) {
            $record = DB::table(self::TABLE)
                ->where('source_type', 'staff')
                ->where('source_id', $ticketId)
                ->first();

            DB::table(self::TABLE)
                ->where('source_type', 'staff')
                ->where('source_id', $ticketId)
                ->delete();

            if ($record) {
                $this->vectors->delete((int) $record->id);
            }

            return [
                'id' => null,
                'is_approved' => false,
                'is_in_library' => false,
                'approval_blocked' => false,
            ];
        }

        try {
            $result = $this->storeEntry(
                'staff',
                $ticketId,
                [
                    'session_id' => $sessionId ? (int) $sessionId : null,
                    'user_message_id' => $userMessageId
                        ? (int) $userMessageId
                        : null,
                    'assistant_message_id' => null,
                    'ticket_id' => $ticketId,
                ],
                $question,
                $answer,
                (int) $adminId
            );

            return array_merge($result, [
                'is_approved' => true,
                'approval_blocked' => false,
            ]);
        } catch (InvalidArgumentException $e) {
            DB::table(self::TABLE)
                ->where('source_type', 'staff')
                ->where('source_id', $ticketId)
                ->delete();

            return [
                'id' => null,
                'is_approved' => false,
                'is_in_library' => false,
                'approval_blocked' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Bỏ tick là xóa khỏi kho.
     */
    public function setApproval($libraryId, $approved, $adminId)
    {
        $record = $this->findRecord($libraryId);

        if (!(bool) $approved) {
            DB::table(self::TABLE)
                ->where('id', $record->id)
                ->delete();

            $this->vectors->delete((int) $record->id);

            return [
                'id' => null,
                'is_approved' => false,
                'is_in_library' => false,
            ];
        }

        $review = $this->reviewForLibrary(
            $record->question,
            $record->answer
        );

        if (!$review['can_save']) {
            throw new InvalidArgumentException(
                implode(' ', $review['blocking_reasons'])
            );
        }

        $metadata = $this->metadataFor(
            $record->question,
            $record->answer
        );
        $data = [
            'is_approved' => 1,
            'is_active' => 1,
            'approved_by' => (int) $adminId,
            'approved_at' => now(),
            'updated_at' => now(),
        ];
        $data = array_merge($data, $this->metadataColumnsData($metadata));

        DB::table(self::TABLE)
            ->where('id', $record->id)
            ->update($data);

        $this->vectors->sync(
            (int) $record->id,
            $record->question
        );

        return [
            'id' => (int) $record->id,
            'is_approved' => true,
            'is_in_library' => true,
            'review' => $review,
        ];
    }

    public function updateEntry(
        $libraryId,
        $question,
        $answer,
        $adminId
    ) {
        $record = $this->findRecord($libraryId);
        $question = trim((string) $question);
        $answer = trim(strip_tags((string) $answer));

        $review = $this->reviewForLibrary(
            $question,
            $answer
        );

        if (!$review['can_save']) {
            throw new InvalidArgumentException(
                implode(' ', $review['blocking_reasons'])
            );
        }

        $metadata = $this->metadataFor($question, $answer);
        $data = [
            'question' => $question,
            'normalized_question' => $metadata['analysis']['normalized'],
            'question_hash' => $metadata['analysis']['question_hash'],
            'answer' => $answer,
            'is_approved' => 1,
            'is_active' => 1,
            'approved_by' => (int) $adminId,
            'approved_at' => $record->approved_at ?: now(),
            'updated_at' => now(),
        ];
        $data = array_merge($data, $this->metadataColumnsData($metadata));

        DB::table(self::TABLE)
            ->where('id', $record->id)
            ->update($data);

        $this->vectors->sync(
            (int) $record->id,
            $question
        );

        return [
            'id' => (int) $record->id,
            'is_in_library' => true,
            'review' => $review,
        ];
    }

    public function reviewForLibrary($question, $answer)
    {
        $review = $this->analyzer->reviewForLibrary(
            $question,
            $answer
        );

        if ($this->ticketCommands->isRequest($question)) {
            $review['can_save'] = false;
            $review['hard_block'] = true;
            $review['reuse_ready'] = false;
            $review['blocking_reasons'][] =
                'Lệnh tạo ticket không được lưu làm câu hỏi dùng chung.';
            $review['blocking_reasons'] = array_values(array_unique(
                $review['blocking_reasons']
            ));
        }

        return $review;
    }

    /**
     * true nghĩa là admin được phép lưu.
     * Việc chatbot được tự động tái sử dụng được quyết định bởi reuse_ready.
     */
    public function canUseAsLibrary($question, $answer)
    {
        $review = $this->reviewForLibrary(
            $question,
            $answer
        );

        return (bool) $review['can_save'];
    }

    public function isPersonalLookupQuestion($question)
    {
        $analysis = $this->analyzer->analyze($question);

        return (bool) $analysis['is_personal'];
    }

    /**
     * Tìm câu trả lời theo chiến lược:
     * 1. Khớp chính xác sau chuẩn hóa.
     * 2. Khớp gần đúng cực kỳ bảo thủ.
     * 3. Nếu hai ứng viên gần nhau, không dùng câu nào.
     */
    public function findExactApprovedAnswer($question)
    {
        try {
            $analysis = $this->analyzer->analyze($question);

            if (!$this->canSearchLibrary($analysis, $question)) {
                return null;
            }

            return $this->findExactApprovedAnswerInternal(
                $analysis
            );
        } catch (\Throwable $e) {
            Log::error(
                '[AnswerLibrary] Exact match failed open',
                [
                    'question' => $question,
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]
            );

            return null;
        }
    }

    public function findApprovedAnswer(
        $question,
        array $context = []
    ) {
        try {
            return $this->findApprovedAnswerInternal(
                $question,
                $context
            );
        } catch (\Throwable $e) {
            /*
             * Kho câu trả lời chỉ là lớp tối ưu.
             * Không được phép làm toàn bộ chatbot trả HTTP 500.
             */
            Log::error(
                '[AnswerLibrary] Match failed open',
                [
                    'question' => $question,
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]
            );

            return null;
        }
    }

    private function canSearchLibrary(
        array $analysis,
        $question
    ) {
        return $analysis['raw'] !== ''
            && !$analysis['is_personal']
            && !$analysis['is_ticket_code']
            && !$analysis['is_gibberish']
            && !$this->ticketCommands->isRequest($question);
    }

    private function findExactApprovedAnswerInternal(
        array $analysis
    ) {
        $hasHash = $this->hasMetadataColumn(
            'question_hash'
        );
        $hasNormalized = $this->hasMetadataColumn(
            'normalized_question'
        );

        $metadataCandidates = collect();

        if ($hasHash || $hasNormalized) {
            $metadataCandidates = DB::table(self::TABLE)
                ->where('is_approved', 1)
                ->where('is_active', 1)
                ->where(function ($where) use (
                    $analysis,
                    $hasHash,
                    $hasNormalized
                ) {
                    if ($hasHash) {
                        $where->where(
                            'question_hash',
                            $analysis['question_hash']
                        );
                    }

                    if ($hasNormalized) {
                        if ($hasHash) {
                            $where->orWhere(
                                'normalized_question',
                                $analysis['normalized']
                            );
                        } else {
                            $where->where(
                                'normalized_question',
                                $analysis['normalized']
                            );
                        }
                    }
                })
                ->orderByDesc('use_count')
                ->orderByDesc('updated_at')
                ->limit(50)
                ->get();
        }

        $checkedIds = [];

        foreach ($metadataCandidates as $candidate) {
            $checkedIds[] = (int) $candidate->id;
            $profile = $this->candidateProfile($candidate);

            if (!$this->matcher->isExactMatch(
                $analysis,
                $profile
            )) {
                continue;
            }

            if (!$this->candidateCanAnswer(
                $analysis,
                $candidate,
                $profile,
                true
            )) {
                continue;
            }

            return $this->formatMatch(
                $candidate,
                1.0,
                'exact',
                [
                    'exact_strategy' =>
                        'metadata_and_runtime_normalized',
                    'lexical_score' => 1.0,
                    'semantic_score' => null,
                    'hybrid_score' => 1.0,
                ]
            );
        }

        /*
         * Fallback cho metadata cũ hoặc hash được tạo bởi analyzer cũ.
         */
        $runtimeLimit = (int) config(
            'chatbot.matcher.exact_runtime_scan_limit',
            5000
        );
        $runtimeLimit = max(
            100,
            min(20000, $runtimeLimit)
        );

        $runtimeQuery = DB::table(self::TABLE)
            ->where('is_approved', 1)
            ->where('is_active', 1);

        if (!empty($checkedIds)) {
            $runtimeQuery->whereNotIn('id', $checkedIds);
        }

        $runtimeCandidates = $runtimeQuery
            ->orderByDesc('use_count')
            ->orderByDesc('updated_at')
            ->limit($runtimeLimit)
            ->get();

        foreach ($runtimeCandidates as $candidate) {
            $profile = $this->candidateProfile($candidate);

            if (!$this->matcher->isExactMatch(
                $analysis,
                $profile
            )) {
                continue;
            }

            if (!$this->candidateCanAnswer(
                $analysis,
                $candidate,
                $profile,
                true
            )) {
                continue;
            }

            $this->refreshCandidateMetadataFailOpen(
                $candidate,
                $analysis
            );

            return $this->formatMatch(
                $candidate,
                1.0,
                'exact_runtime',
                [
                    'exact_strategy' =>
                        'runtime_normalized_scan',
                    'lexical_score' => 1.0,
                    'semantic_score' => null,
                    'hybrid_score' => 1.0,
                ]
            );
        }

        return null;
    }

    private function refreshCandidateMetadataFailOpen(
        $candidate,
        array $analysis
    ) {
        try {
            $data = ['updated_at' => now()];

            if ($this->hasMetadataColumn(
                'normalized_question'
            )) {
                $data['normalized_question'] =
                    $analysis['normalized'];
            }

            if ($this->hasMetadataColumn(
                'question_hash'
            )) {
                $data['question_hash'] =
                    $analysis['question_hash'];
            }

            DB::table(self::TABLE)
                ->where('id', (int) $candidate->id)
                ->update($data);
        } catch (\Throwable $e) {
            Log::warning(
                '[AnswerLibrary] Metadata refresh skipped',
                [
                    'library_id' => (int) $candidate->id,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    private function findApprovedAnswerInternal(
        $question,
        array $context = []
    ) {
        $analysis = $this->analyzer->analyze($question);

        if (!$this->canSearchLibrary($analysis, $question)) {
            return null;
        }

        $exactMatch = $this->findExactApprovedAnswerInternal(
            $analysis
        );

        if ($exactMatch) {
            return $exactMatch;
        }

        if (!$analysis['allow_approximate_match']) {
            return null;
        }

        /*
         * 2. Quét toàn bộ kho đã duyệt trong giới hạn cấu hình.
         *
         * Không lọc cứng theo intent, tên ngành hoặc chủ đề.
         * Vì vậy chủ đề mới không cần sửa code.
         */
        $maxCandidates = (int) config(
            'chatbot.matcher.max_candidates',
            1000
        );
        $maxCandidates = max(
            50,
            min(5000, $maxCandidates)
        );

        $candidates = DB::table(self::TABLE)
            ->where('is_approved', 1)
            ->where('is_active', 1)
            ->orderByDesc('use_count')
            ->orderByDesc('updated_at')
            ->limit($maxCandidates)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $candidateIds = [];

        foreach ($candidates as $candidate) {
            $candidateIds[] = (int) $candidate->id;
        }

        /*
         * Embedding là tín hiệu bổ sung.
         * API hoặc bảng vector lỗi thì lexical vẫn hoạt động.
         */
        $queryVector = null;
        $candidateVectors = [];

        if ($this->vectors->isAvailable()) {
            $candidateVectors = $this->vectors->getMany(
                $candidateIds
            );

            if (!empty($candidateVectors)) {
                $queryVector = $this->embeddings->embed(
                    $analysis['raw']
                );
            }
        }

        $ranked = [];

        foreach ($candidates as $candidate) {
            $profile = $this->candidateProfile($candidate);

            if (!$this->candidateCanAnswer(
                $analysis,
                $candidate,
                $profile,
                false
            )) {
                continue;
            }

            $lexical = $this->matcher->compare(
                $analysis,
                $profile
            );

            $semanticScore = null;
            $candidateId = (int) $candidate->id;

            if (
                is_array($queryVector)
                && isset($candidateVectors[$candidateId])
            ) {
                $semanticScore =
                    $this->embeddings->cosineSimilarity(
                        $queryVector,
                        $candidateVectors[$candidateId]
                    );
            }

            $combined = $this->hybridScore(
                $lexical,
                $semanticScore
            );

            if (!$combined['passes']) {
                continue;
            }

            $ranked[] = [
                'candidate' => $candidate,
                'score' => $combined['score'],
                'details' => $combined,
            ];
        }

        if (empty($ranked)) {
            return null;
        }

        usort($ranked, function ($left, $right) {
            if ($left['score'] === $right['score']) {
                return (int) $right['candidate']->use_count
                    <=> (int) $left['candidate']->use_count;
            }

            return $left['score'] < $right['score']
                ? 1
                : -1;
        });

        $best = $ranked[0];
        $bestFingerprint = $this->answerFingerprint(
            $best['candidate']
        );

        /*
         * Bỏ qua các câu chuẩn khác nhau nhưng cùng một đáp án.
         * Chỉ tính margin với đáp án khác nội dung.
         */
        $secondDistinct = null;

        foreach (array_slice($ranked, 1) as $item) {
            if (
                $this->answerFingerprint(
                    $item['candidate']
                ) !== $bestFingerprint
            ) {
                $secondDistinct = $item;
                break;
            }
        }

        $secondScore = $secondDistinct
            ? (float) $secondDistinct['score']
            : 0.0;
        $margin = (float) $best['score'] - $secondScore;
        $minimumMargin = (float) config(
            'chatbot.matcher.minimum_margin',
            0.03
        );

        if (
            $secondDistinct
            && $margin < $minimumMargin
        ) {
            Log::info(
                '[AnswerLibrary] Ambiguous distinct answers',
                [
                    'question' => $question,
                    'best_library_id' =>
                        (int) $best['candidate']->id,
                    'best_score' =>
                        (float) $best['score'],
                    'second_library_id' =>
                        (int) $secondDistinct['candidate']->id,
                    'second_score' => $secondScore,
                    'margin' => $margin,
                ]
            );

            return null;
        }

        $details = $best['details'];
        $details['runner_up_score'] = round(
            $secondScore,
            6
        );
        $details['margin'] = round($margin, 6);

        return $this->formatMatch(
            $best['candidate'],
            (float) $best['score'],
            'hybrid',
            $details
        );
    }

    private function hybridScore(
        array $lexical,
        $semanticScore
    ) {
        $lexicalScore = isset($lexical['score'])
            ? (float) $lexical['score']
            : 0.0;
        $semanticAvailable = is_numeric($semanticScore);
        $semanticScore = $semanticAvailable
            ? max(0.0, min(1.0, (float) $semanticScore))
            : null;

        $hybridScore = $semanticAvailable
            ? ($semanticScore * 0.72)
                + ($lexicalScore * 0.28)
            : $lexicalScore;

        $lexicalPass = !empty($lexical['passes'])
            || !empty($lexical['strong_core_match'])
            || $lexicalScore >= (float) config(
                'chatbot.matcher.lexical_threshold',
                0.90
            );

        $semanticPass = $semanticAvailable
            && $semanticScore >= (float) config(
                'chatbot.matcher.semantic_threshold',
                0.88
            );

        $combinedPass = $semanticAvailable
            && $semanticScore >= (float) config(
                'chatbot.matcher.hybrid_semantic_floor',
                0.78
            )
            && $lexicalScore >= (float) config(
                'chatbot.matcher.hybrid_lexical_floor',
                0.25
            )
            && $hybridScore >= (float) config(
                'chatbot.matcher.hybrid_threshold',
                0.80
            );

        return [
            'passes' => $lexicalPass
                || $semanticPass
                || $combinedPass,
            'score' => round($hybridScore, 6),
            'lexical_score' => round(
                $lexicalScore,
                6
            ),
            'semantic_score' => $semanticAvailable
                ? round($semanticScore, 6)
                : null,
            'lexical_pass' => $lexicalPass,
            'semantic_pass' => $semanticPass,
            'hybrid_pass' => $combinedPass,
            'lexical_details' => $lexical,
        ];
    }

    public function diagnoseApprovedAnswer($question)
    {
        $analysis = $this->analyzer->analyze($question);
        $exactMatch = $this->findExactApprovedAnswer(
            $question
        );
        $match = $exactMatch
            ?: $this->findApprovedAnswer($question);

        $runtimeExactCount = 0;

        try {
            $rows = DB::table(self::TABLE)
                ->where('is_approved', 1)
                ->where('is_active', 1)
                ->limit((int) config(
                    'chatbot.matcher.exact_runtime_scan_limit',
                    5000
                ))
                ->get();

            foreach ($rows as $row) {
                $profile = $this->candidateProfile($row);

                if ($this->matcher->isExactMatch(
                    $analysis,
                    $profile
                )) {
                    $runtimeExactCount++;
                }
            }
        } catch (\Throwable $e) {
            $runtimeExactCount = -1;
        }

        return [
            'question' => $question,
            'analysis' => [
                'normalized' => $analysis['normalized'],
                'tokens' => $analysis['tokens'],
                'is_question' => isset($analysis['is_question'])
                    ? $analysis['is_question']
                    : null,
                'is_yes_no_question' => isset(
                    $analysis['is_yes_no_question']
                )
                    ? $analysis['is_yes_no_question']
                    : null,
                'is_negation' => $analysis['is_negation'],
                'polarity' => isset($analysis['polarity'])
                    ? $analysis['polarity']
                    : null,
                'requires_exact_match' =>
                    $analysis['requires_exact_match'],
                'allow_approximate_match' =>
                    $analysis['allow_approximate_match'],
            ],
            'exact_runtime_candidate_count' =>
                $runtimeExactCount,
            'semantic_enabled' =>
                $this->embeddings->isEnabled(),
            'vector_table_available' =>
                $this->vectors->isAvailable(),
            'approved_active_count' => DB::table(self::TABLE)
                ->where('is_approved', 1)
                ->where('is_active', 1)
                ->count(),
            'match' => $match,
        ];
    }

    /**
     * Tường lửa cuối cùng trước khi trả câu từ kho ra giao diện.
     */
    public function isAnswerSafeForDelivery($answer)
    {
        return $this->analyzer->isAnswerQualityAcceptable($answer);
    }

    public function deleteEntry($libraryId, $reason = 'manual_cleanup')
    {
        $libraryId = (int) $libraryId;

        if ($libraryId <= 0) {
            return;
        }

        DB::table(self::TABLE)
            ->where('id', $libraryId)
            ->delete();

        $this->vectors->delete($libraryId);

        Log::warning('[AnswerLibrary] Deleted unsafe entry', [
            'library_id' => $libraryId,
            'reason' => $reason,
        ]);
    }

    public function markUsed($libraryId)
    {
        DB::table(self::TABLE)
            ->where('id', (int) $libraryId)
            ->increment('use_count', 1, [
                'last_used_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Cập nhật metadata cho dữ liệu cũ sau migration.
     */
    public function rebuildMetadata($libraryId)
    {
        $record = $this->findRecord($libraryId);

        if (!$this->canUseAsLibrary($record->question, $record->answer)) {
            return [
                'updated' => false,
                'valid' => false,
            ];
        }

        $metadata = $this->metadataFor(
            $record->question,
            $record->answer
        );

        DB::table(self::TABLE)
            ->where('id', $record->id)
            ->update(array_merge(
                [
                    'normalized_question' =>
                        $metadata['analysis']['normalized'],
                    'question_hash' =>
                        $metadata['analysis']['question_hash'],
                    'updated_at' => now(),
                ],
                $this->metadataColumnsData($metadata)
            ));

        $this->vectors->sync(
            (int) $record->id,
            $record->question
        );

        return [
            'updated' => true,
            'valid' => true,
        ];
    }

    public function rebuildVector($libraryId)
    {
        $record = DB::table(self::TABLE)
            ->where('id', (int) $libraryId)
            ->where('is_approved', 1)
            ->where('is_active', 1)
            ->first();

        if (!$record) {
            return false;
        }

        return $this->vectors->sync(
            (int) $record->id,
            $record->question
        );
    }

    private function storeEntry(
        $sourceType,
        $sourceId,
        array $references,
        $question,
        $answer,
        $adminId
    ) {
        $question = trim((string) $question);
        $answer = trim(strip_tags((string) $answer));

        $review = $this->reviewForLibrary(
            $question,
            $answer
        );

        if (!$review['can_save']) {
            throw new InvalidArgumentException(
                implode(' ', $review['blocking_reasons'])
            );
        }

        $metadata = $this->metadataFor($question, $answer);
        $existing = DB::table(self::TABLE)
            ->where('source_type', $sourceType)
            ->where('source_id', (int) $sourceId)
            ->first();

        $data = array_merge($references, [
            'question' => $question,
            'normalized_question' => $metadata['analysis']['normalized'],
            'question_hash' => $metadata['analysis']['question_hash'],
            'answer' => $answer,
            'is_approved' => 1,
            'is_active' => 1,
            'created_by' => (int) $adminId,
            'approved_by' => (int) $adminId,
            'approved_at' => now(),
            'updated_at' => now(),
        ]);
        $data = array_merge($data, $this->metadataColumnsData($metadata));

        if ($existing) {
            DB::table(self::TABLE)
                ->where('id', $existing->id)
                ->update($data);

            $this->vectors->sync(
                (int) $existing->id,
                $question
            );

            return [
                'id' => (int) $existing->id,
                'is_in_library' => true,
                'review' => $review,
            ];
        }

        $data['source_type'] = $sourceType;
        $data['source_id'] = (int) $sourceId;
        $data['use_count'] = 0;
        $data['last_used_at'] = null;
        $data['created_at'] = now();

        $id = DB::table(self::TABLE)->insertGetId($data);

        $this->vectors->sync(
            (int) $id,
            $question
        );

        return [
            'id' => (int) $id,
            'is_in_library' => true,
            'review' => $review,
        ];
    }

    private function metadataFor($question, $answer)
    {
        $analysis = $this->analyzer->analyze($question);
        $answerAnalysis = $this->analyzer->analyze($answer);
        $validFrom = null;
        $validUntil = null;
        $explicitYears = isset($analysis['entities']['years'])
            ? $analysis['entities']['years']
            : [];

        if (empty($explicitYears)) {
            $explicitYears = isset($answerAnalysis['entities']['years'])
                ? $answerAnalysis['entities']['years']
                : [];
        }

        $admissionYear = !empty($explicitYears)
            ? (int) $explicitYears[0]
            : (
                $analysis['is_time_sensitive']
                    ? (int) now()->year
                    : null
            );
        $analysis['resolved_year'] = $admissionYear;

        if ($analysis['is_time_sensitive'] && $admissionYear) {
            $validFrom = $admissionYear . '-01-01';
            $validUntil = $admissionYear . '-12-31';
        }

        return [
            'analysis' => $analysis,
            'answer_hash' => hash(
                'sha256',
                $this->analyzer->normalize($answer)
            ),
            'quality_score' => $this->answerQualityScore($answer),
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
        ];
    }

    private function metadataColumnsData(array $metadata)
    {
        $analysis = $metadata['analysis'];
        $data = [];

        if ($this->hasMetadataColumn('intent_key')) {
            $data['intent_key'] = $analysis['primary_intent'];
        }

        if ($this->hasMetadataColumn('intent_signature')) {
            $data['intent_signature'] = $analysis['intent_signature'];
        }

        if ($this->hasMetadataColumn('entities_json')) {
            $data['entities_json'] = json_encode(
                $analysis['entities'],
                JSON_UNESCAPED_UNICODE
            );
        }

        if ($this->hasMetadataColumn('analysis_json')) {
            $data['analysis_json'] = json_encode(
                [
                    'is_multi_intent' => $analysis['is_multi_intent'],
                    'is_time_sensitive' => $analysis['is_time_sensitive'],
                    'is_comparison' => $analysis['is_comparison'],
                    'is_negation' => $analysis['is_negation'],
                    'is_conditional' => $analysis['is_conditional'],
                    'requires_exact_match' =>
                        $analysis['requires_exact_match'],
                ],
                JSON_UNESCAPED_UNICODE
            );
        }

        if ($this->hasMetadataColumn('admission_year')) {
            $data['admission_year'] = $analysis['resolved_year'];
        }

        if ($this->hasMetadataColumn('valid_from')) {
            $data['valid_from'] = $metadata['valid_from'];
        }

        if ($this->hasMetadataColumn('valid_until')) {
            $data['valid_until'] = $metadata['valid_until'];
        }

        if ($this->hasMetadataColumn('answer_hash')) {
            $data['answer_hash'] = $metadata['answer_hash'];
        }

        if ($this->hasMetadataColumn('quality_score')) {
            $data['quality_score'] = $metadata['quality_score'];
        }

        return $data;
    }

    private function answerFingerprint($candidate)
    {
        if (
            isset($candidate->answer_hash)
            && trim((string) $candidate->answer_hash) !== ''
        ) {
            return (string) $candidate->answer_hash;
        }

        return $this->matcher->answerFingerprint(
            isset($candidate->answer)
                ? $candidate->answer
                : '',
            $this->analyzer
        );
    }

    private function deleteInvalidCandidate($candidate, $reason)
    {
        if (!isset($candidate->id)) {
            return;
        }

        $libraryId = (int) $candidate->id;

        DB::table(self::TABLE)
            ->where('id', $libraryId)
            ->delete();

        $this->vectors->delete($libraryId);

        Log::warning('[AnswerLibrary] Purged invalid candidate', [
            'library_id' => $libraryId,
            'source_type' => isset($candidate->source_type)
                ? $candidate->source_type
                : null,
            'source_id' => isset($candidate->source_id)
                ? $candidate->source_id
                : null,
            'reason' => $reason,
        ]);
    }

    private function candidateProfile($candidate)
    {
        /*
         * Luôn phân tích lại bằng analyzer tổng quát.
         *
         * Không nạp intent_key, intent_signature hoặc entities_json
         * cũ vì các trường đó có thể được tạo từ danh sách hard-code.
         */
        $analysis = $this->analyzer->analyze(
            $candidate->question
        );
        $answerAnalysis = $this->analyzer->analyze(
            $candidate->answer
        );

        $explicitYears = isset(
            $analysis['entities']['years']
        )
            ? $analysis['entities']['years']
            : [];

        if (empty($explicitYears)) {
            $explicitYears = isset(
                $answerAnalysis['entities']['years']
            )
                ? $answerAnalysis['entities']['years']
                : [];
        }

        $analysis['resolved_year'] = !empty($explicitYears)
            ? (int) $explicitYears[0]
            : null;

        if (
            isset($candidate->admission_year)
            && $candidate->admission_year
        ) {
            $analysis['resolved_year'] =
                (int) $candidate->admission_year;
        }

        return $analysis;
    }

    private function candidateCanAnswer(
        array $queryAnalysis,
        $candidate,
        array $candidateProfile,
        $isExact
    ) {
        $review = $this->reviewForLibrary(
            $candidate->question,
            $candidate->answer
        );

        if (!$review['can_save']) {
            Log::warning(
                '[AnswerLibrary] Candidate blocked at read time',
                [
                    'library_id' => isset($candidate->id)
                        ? (int) $candidate->id
                        : null,
                    'blocking_reasons' =>
                        $review['blocking_reasons'],
                ]
            );

            return false;
        }

        if (
            !$review['reuse_ready']
            && !$isExact
        ) {
            return false;
        }

        if (
            $isExact
            && !$this->isAnswerSafeForDelivery(
                $candidate->answer
            )
        ) {
            return false;
        }

        if (!$this->isCurrentlyValid(
            $candidate,
            $queryAnalysis
        )) {
            return false;
        }

        if (!$isExact) {
            /*
             * Không so intent_signature cố định.
             * Chủ đề mới được đánh giá trực tiếp bằng token và n-gram.
             */
            if (
                $candidateProfile['requires_exact_match']
                || !$candidateProfile['allow_approximate_match']
            ) {
                return false;
            }
        }

        if (!$this->yearsCompatible(
            $queryAnalysis,
            $candidate,
            $candidateProfile
        )) {
            return false;
        }

        if (!$this->polarityCompatible(
            $queryAnalysis,
            $candidateProfile,
            $isExact
        )) {
            return false;
        }

        if (!$this->entitiesCompatible(
            $queryAnalysis['entities'],
            $candidateProfile['entities'],
            $queryAnalysis['primary_intent'],
            $isExact
        )) {
            return false;
        }

        return true;
    }

    private function yearsCompatible(
        array $queryAnalysis,
        $candidate,
        array $candidateProfile
    ) {
        if (!$queryAnalysis['is_time_sensitive']) {
            return true;
        }

        $queryYear = $queryAnalysis['resolved_year'];
        $candidateYear = null;

        if (isset($candidate->admission_year) && $candidate->admission_year) {
            $candidateYear = (int) $candidate->admission_year;
        } elseif (isset($candidateProfile['resolved_year'])) {
            $candidateYear = $candidateProfile['resolved_year'];
        }

        if (!$queryYear || !$candidateYear) {
            return false;
        }

        return (int) $queryYear === (int) $candidateYear;
    }

    private function polarityCompatible(
        array $queryAnalysis,
        array $candidateAnalysis,
        $isExact
    ) {
        if ($isExact) {
            return true;
        }

        $queryPolarity = isset($queryAnalysis['polarity'])
            ? $queryAnalysis['polarity']
            : 'neutral';
        $candidatePolarity = isset(
            $candidateAnalysis['polarity']
        )
            ? $candidateAnalysis['polarity']
            : 'neutral';

        /*
         * Câu hỏi Có/Không là trung tính, không bị xem là phủ định.
         * Chỉ chặn khi một câu là khẳng định phủ định thật
         * và câu còn lại không cùng cực tính.
         */
        if (
            $queryPolarity !== 'neutral'
            || $candidatePolarity !== 'neutral'
        ) {
            return $queryPolarity === $candidatePolarity;
        }

        return true;
    }

    private function entitiesCompatible(
        array $queryEntities,
        array $candidateEntities,
        $primaryIntent,
        $isExact
    ) {
        if ($isExact) {
            return true;
        }

        /*
         * Không kiểm tra tên ngành/phương thức/tổ hợp.
         *
         * Chỉ giữ các cấu trúc tổng quát có khả năng làm thay đổi
         * bản chất câu hỏi: mã và con số cụ thể.
         */
        foreach (['codes', 'numbers'] as $key) {
            $queryValues = isset($queryEntities[$key])
                ? $queryEntities[$key]
                : [];
            $candidateValues = isset(
                $candidateEntities[$key]
            )
                ? $candidateEntities[$key]
                : [];

            if (empty($queryValues) && empty($candidateValues)) {
                continue;
            }

            sort($queryValues);
            sort($candidateValues);

            if ($queryValues !== $candidateValues) {
                return false;
            }
        }

        return true;
    }

    private function isCurrentlyValid(
        $candidate,
        array $queryAnalysis
    ) {
        if (!empty($queryAnalysis['has_explicit_year'])) {
            return true;
        }

        $today = now()->toDateString();

        if (
            isset($candidate->valid_from)
            && $candidate->valid_from
            && $today < (string) $candidate->valid_from
        ) {
            return false;
        }

        if (
            isset($candidate->valid_until)
            && $candidate->valid_until
            && $today > (string) $candidate->valid_until
        ) {
            return false;
        }

        return true;
    }

    private function answerQualityScore($answer)
    {
        $normalized = $this->analyzer->normalize($answer);
        $tokens = $this->analyzer->significantTokens(
            $normalized
        );
        $lengthScore = min(1.0, count($tokens) / 20);
        $sentenceScore = preg_match(
            '/[\.\!\?]/u',
            (string) $answer
        )
            ? 1.0
            : 0.8;

        return round(
            ($lengthScore * 0.7)
                + ($sentenceScore * 0.3),
            4
        );
    }

    private function findRecord($libraryId)
    {
        $record = DB::table(self::TABLE)
            ->where('id', (int) $libraryId)
            ->first();

        if (!$record) {
            throw new InvalidArgumentException(
                'Không tìm thấy câu trả lời trong kho.'
            );
        }

        return $record;
    }

    private function hasMetadataColumn($column)
    {
        if (self::$metadataColumns === null) {
            self::$metadataColumns = [];

            foreach ([
                'normalized_question',
                'question_hash',
                'intent_key',
                'intent_signature',
                'entities_json',
                'analysis_json',
                'admission_year',
                'valid_from',
                'valid_until',
                'answer_hash',
                'quality_score',
            ] as $candidateColumn) {
                try {
                    self::$metadataColumns[$candidateColumn] =
                        Schema::hasColumn(
                            self::TABLE,
                            $candidateColumn
                        );
                } catch (\Throwable $e) {
                    self::$metadataColumns[$candidateColumn] =
                        false;
                }
            }
        }

        return isset(self::$metadataColumns[$column])
            && self::$metadataColumns[$column];
    }

    private function formatMatch(
        $record,
        $score,
        $matchType,
        array $details
    ) {
        return [
            'library_id' => (int) $record->id,
            'source_type' => $record->source_type,
            'question' => $record->question,
            'answer' => $record->answer,
            'score' => round((float) $score, 4),
            'match_type' => $matchType,
            'match_details' => $details,
        ];
    }
}
