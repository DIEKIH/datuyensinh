<?php

namespace App\Http\Controllers;

use App\Services\AdmissionLeadScoringService;
use App\Services\AdmissionRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdmissionWebhookController extends Controller
{
    private $scoring;
    private $rag;

    public function __construct(AdmissionLeadScoringService $scoring, AdmissionRagService $rag)
    {
        $this->scoring = $scoring;
        $this->rag = $rag;
    }

    public function upsertLead(Request $request)
    {
        try {
            $data = $request->all();

            DB::table('admission_n8n_logs')->insert([
                'event_type' => 'webhook_upsert_lead',
                'workflow' => 'messenger_flow',
                'status' => 'received',
                'message' => 'Received webhook from n8n',
                'payload' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $channel = strtolower(trim((string) $request->input('channel', 'facebook')));
            $content = trim((string) $request->input(
                'question',
                $request->input('content', '')
            ));
            $mediaUrl = $request->input('media_url');
            $senderId = trim((string) $request->input('sender_id', ''));
            $senderName = trim((string) $request->input('sender_name', ''));
            $isComment = (bool) $request->input('is_comment', false);
            $interactionType = strtolower(trim((string) $request->input(
                'interaction_type',
                $isComment ? 'comment' : 'chat'
            )));

            $leadId = null;
            $lead = null;
            $account = null;
            $scoreData = null;
            $messengerPsid = null;
            $leadWasExisting = false;

            if ($senderId !== '') {
                $account = DB::table('admission_lead_channel_accounts')
                    ->where('channel', $channel)
                    ->where('account_id', $senderId)
                    ->first();

                if ($account) {
                    $leadId = $account->lead_id;
                    $lead = DB::table('admission_leads')
                        ->where('id', $leadId)
                        ->first();
                } else {
                    $lead = DB::table('admission_leads')
                        ->where('profile->sender_id', $senderId)
                        ->first();

                    if ($lead) {
                        $leadId = $lead->id;
                    }
                }
            }

            $leadWasExisting = $lead !== null;

            if (!$lead && $senderId !== '') {
                $profile = [
                    'sender_id' => $senderId,
                ];

                if ($senderName !== '') {
                    $profile['full_name'] = $senderName;
                }

                // Chỉ sender_id nhận từ sự kiện chat Messenger mới được xem là PSID.
                if ($channel === 'facebook'
                    && in_array($interactionType, ['chat', 'get_started'], true)) {
                    $profile['messenger_psid'] = $senderId;
                }

                $leadId = DB::table('admission_leads')->insertGetId([
                    'full_name' => $senderName !== '' ? $senderName : null,
                    'channel' => $channel,
                    'profile' => json_encode($profile),
                    'score' => 0,
                    'score_grade' => 'cold',
                    'status' => 'new',
                    'last_interaction_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $lead = DB::table('admission_leads')
                    ->where('id', $leadId)
                    ->first();
            }

            if (!$leadId) {
                return response()->json([
                    'success' => true,
                    'skipped' => true,
                    'message' => 'Không có sender_id nên không thể nhận diện lead.',
                    'data' => null,
                ]);
            }

            $profile = json_decode($lead->profile ?? '{}', true);
            if (!is_array($profile)) {
                $profile = [];
            }

            $leadUpdates = [];
            $profileChanged = false;

            if ($senderId !== '' && empty($profile['sender_id'])) {
                $profile['sender_id'] = $senderId;
                $profileChanged = true;
            }

            if ($senderName !== '') {
                $profile['full_name'] = $senderName;
                $profileChanged = true;

                // Không ghi đè tên do thí sinh tự khai trên form.
                if (empty($lead->full_name)) {
                    $leadUpdates['full_name'] = $senderName;
                }
            }

            if (
                $channel === 'facebook'
                && in_array($interactionType, ['chat', 'get_started'], true)
                && $senderId !== ''
            ) {
                $profile['messenger_psid'] = $senderId;
                $profileChanged = true;
            }

            if ($profileChanged) {
                $leadUpdates['profile'] = json_encode($profile);
            }

            if (!empty($leadUpdates)) {
                $leadUpdates['updated_at'] = now();
                DB::table('admission_leads')
                    ->where('id', $leadId)
                    ->update($leadUpdates);
            }

            $accountData = [
                'lead_id' => $leadId,
                'last_interaction_at' => now(),
                'updated_at' => now(),
            ];

            if (!$account) {
                $accountData['created_at'] = now();
            }

            DB::table('admission_lead_channel_accounts')->updateOrInsert(
                [
                    'channel' => $channel,
                    'account_id' => $senderId,
                ],
                $accountData
            );

            if (
                $channel === 'facebook'
                && in_array($interactionType, ['chat', 'get_started'], true)
                && $senderId !== ''
            ) {
                $messengerPsid = $senderId;
            }

            /*
             * Messenger onboarding:
             * - Get Started dùng PSID để nhận diện người mới/cũ.
             * - Chỉ Lead mới mới bị hỏi thông tin thí sinh.
             * - Trạng thái lưu trong admission_leads.profile, không cần bảng mới.
             */
            $onboardingResult = $this->handleMessengerOnboarding(
                $leadId,
                $leadWasExisting,
                $channel,
                $interactionType,
                $content
            );

            /*
             * Nếu SĐT/Email cho thấy đây là Lead đã có từ Website/Chatbot,
             * onboarding sẽ hợp nhất hồ sơ Messenger vào Lead trung tâm đó.
             */
            if (
                is_array($onboardingResult)
                && !empty($onboardingResult['resolved_lead_id'])
            ) {
                $leadId = (int) $onboardingResult['resolved_lead_id'];
                unset($onboardingResult['resolved_lead_id']);

                $lead = DB::table('admission_leads')
                    ->where('id', $leadId)
                    ->first();

                $account = DB::table('admission_lead_channel_accounts')
                    ->where('channel', $channel)
                    ->where('account_id', $senderId)
                    ->first();
            }

            if (
                $content !== ''
                || in_array($interactionType, ['like', 'share'], true)
            ) {
                DB::table('admission_lead_activities')->insert([
                    'lead_id' => $leadId,
                    'channel' => $channel,
                    'type' => $interactionType,
                    'external_id' => $request->input('comment_id'),
                    'direction' => 'inbound',
                    'content' => $content,
                    'payload' => json_encode($data),
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (
                    $content !== ''
                    && !in_array($interactionType, ['like', 'share'], true)
                ) {
                    $extracted = $this->rag->extractLeadInfo($content);

                    if (!empty($extracted)) {
                        $lead = DB::table('admission_leads')
                            ->where('id', $leadId)
                            ->first();

                        $profile = json_decode($lead->profile ?? '{}', true);
                        if (!is_array($profile)) {
                            $profile = [];
                        }

                        $extractedUpdates = [];

                        if (!empty($extracted['phone'])) {
                            $profile['phone'] = $extracted['phone'];
                            $extractedUpdates['phone'] = $extracted['phone'];
                        }

                        if (!empty($extracted['email'])) {
                            $profile['email'] = $extracted['email'];
                            $extractedUpdates['email'] = $extracted['email'];
                        }

                        if (!empty($extracted['major'])) {
                            $profile['major'] = $extracted['major'];
                            $extractedUpdates['intended_major'] = $extracted['major'];
                        }

                        if (!empty($extracted['intent'])) {
                            $profile['intent'] = $extracted['intent'];
                        }

                        if (!empty($extractedUpdates) || !empty($extracted['intent'])) {
                            $extractedUpdates['profile'] = json_encode($profile);
                            $extractedUpdates['updated_at'] = now();

                            DB::table('admission_leads')
                                ->where('id', $leadId)
                                ->update($extractedUpdates);
                        }
                    }
                }

                // Nạp lại lead sau khi đã cập nhật tên/profile để chấm điểm đúng dữ liệu mới.
                $lead = DB::table('admission_leads')
                    ->where('id', $leadId)
                    ->first();

                $activities = DB::table('admission_lead_activities')
                    ->where('lead_id', $leadId)
                    ->get()
                    ->map(function ($activity) {
                        return (array) $activity;
                    })
                    ->toArray();

                $scoreData = $this->scoring->score((array) $lead, $activities);

                // --- GHI NHAN LOG CHAM DIEM ---
                $matchedCriteria = $scoreData['matched_criteria'] ?? [];
                $existingLogs = DB::table('admission_lead_score_logs')
                    ->where('lead_id', $leadId)
                    ->pluck('criterion_id')
                    ->toArray();

                foreach ($matchedCriteria as $mc) {
                    $cId = $mc['criterion_id'] ?? null;
                    if ($cId && !in_array($cId, $existingLogs)) {
                        DB::table('admission_lead_score_logs')->insert([
                            'lead_id' => $leadId,
                            'channel_account_id' => $account ? $account->id : null,
                            'criterion_id' => $cId,
                            'action_type' => $interactionType,
                            'score_added' => $mc['weight'] ?? 0,
                            'source_channel' => $channel,
                            'external_id' => $request->input('comment_id', null),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                // -----------------------------

                $oldGrade = strtolower((string) ($lead->score_grade ?? 'cold'));
                $newGrade = strtolower((string) ($scoreData['lead_level'] ?? 'cold'));

                DB::table('admission_leads')
                    ->where('id', $leadId)
                    ->update([
                        'score' => $scoreData['lead_score'] ?? 0,
                        'score_grade' => $newGrade,
                        'last_interaction_at' => now(),
                        'updated_at' => now(),
                    ]);

                $latestLead = DB::table('admission_leads')
                    ->where('id', $leadId)
                    ->first();
                $latestProfile = json_decode($latestLead->profile ?? '{}', true);
                if (!is_array($latestProfile)) {
                    $latestProfile = [];
                }

                $messengerPsid = $latestProfile['messenger_psid'] ?? null;

                if (
                    $oldGrade !== 'hot'
                    && $newGrade === 'hot'
                    && $channel === 'facebook'
                    && !empty($messengerPsid)
                ) {
                    \App\Jobs\SendLeadToN8n::dispatch([
                        'event_type' => 'lead_became_hot',
                        'data' => [
                            'campaign_name' =>
                                'Chăm sóc Hot Lead tự động',
                            'campaign_mode' => 'auto_hot',
                            'channel' => 'messenger',
                            'sender_id' => $messengerPsid,
                            'lead_id' => $leadId,
                            'full_name' =>
                                $latestLead->full_name ?? '',
                            'intended_major' =>
                                $latestLead->intended_major ?? '',
                            'score' =>
                                $scoreData['lead_score'] ?? 0,
                            'message' =>
                                'Chúc mừng {name}! Bạn đã trở thành '
                                . 'ứng viên tiềm năng của CTUT. '
                                . 'Nhà trường sẽ tiếp tục gửi các '
                                . 'thông tin tuyển sinh phù hợp đến bạn.',
                        ],
                    ]);
                }
            }

            $ragResult = null;
            $resumePendingQuestion = trim((string) (
                $onboardingResult['resume_question'] ?? ''
            ));
            $ragQuestion = $resumePendingQuestion !== ''
                ? $resumePendingQuestion
                : $content;

            if (
                (
                    $onboardingResult === null
                    || $resumePendingQuestion !== ''
                )
                && $interactionType === 'chat'
                && ($ragQuestion !== '' || !empty($mediaUrl))
            ) {
                /*
                 * Chỉ Messenger chat mới dùng cơ chế trả lời tự động bằng AI
                 * và chuyển ticket khi gặp câu hỏi khó. Bình luận (comment)
                 * sẽ bị bỏ qua và không nhận phản hồi từ AI.
                 */
                $isMessengerChat =
                    $channel === 'facebook'
                    && $interactionType === 'chat';

                $sessionId = null;
                $isPending = false;

                if ($isMessengerChat) {
                    $sessionId = DB::table('chatbot_sessions')
                        ->where('session_key', $senderId)
                        ->value('id');

                    if ($sessionId) {
                        $isPending = DB::table('chatbot_tickets')
                            ->where('session_id', $sessionId)
                            ->where('status', 'pending')
                            ->exists();
                    }
                }

                if ($isMessengerChat && $isPending) {
                    $ragResult = [
                        'answer' =>
                            'Câu hỏi của bạn đã được chuyển đến nhân viên '
                            . 'tư vấn. Khi có phản hồi, tôi sẽ gửi lại ngay '
                            . 'tại cuộc trò chuyện Messenger này.',
                        'sources' => [],
                        'generated' => false,
                        'handoff' => true,
                    ];

                    DB::table('chatbot_tickets')
                        ->where('session_id', $sessionId)
                        ->where('status', 'pending')
                        ->update([
                            'question' => DB::raw(
                                "CONCAT(question, '\n\n[Tin nhắn mới]: ', "
                                . DB::getPdo()->quote($ragQuestion)
                                . ')'
                            ),
                            'updated_at' => now(),
                        ]);
                } else {
                    $ragResult = $this->rag->answer(
                        $ragQuestion,
                        $senderId,
                        $mediaUrl
                    );

                    $answerText = trim(
                        (string) ($ragResult['answer'] ?? '')
                    );

                    if (
                        $resumePendingQuestion !== ''
                        && $answerText !== ''
                    ) {
                        $answerText =
                            'Cảm ơn bạn, mình đã ghi nhận thông tin. '
                            . "\n\n"
                            . $answerText;
                        $ragResult['answer'] = $answerText;
                    }

                    $needsHandoff =
                        $isMessengerChat
                        && $this->messengerAnswerNeedsHandoff(
                            $answerText
                        );

                    if ($needsHandoff) {
                        /*
                         * Messenger không yêu cầu người dùng xác nhận tạo ticket
                         * và cũng không bắt tra cứu mã ticket như Website.
                         */
                        $answerText =
                            'Tôi chưa có đủ dữ liệu cụ thể để trả lời chính '
                            . 'xác câu hỏi này. Tôi đã chuyển câu hỏi của bạn '
                            . 'đến nhân viên tư vấn. Khi có phản hồi, tôi sẽ '
                            . 'gửi lại ngay tại cuộc trò chuyện Messenger này.';

                        $ragResult['answer'] = $answerText;
                        $ragResult['generated'] = false;
                        $ragResult['handoff'] = true;

                        if (!$sessionId) {
                            $sessionId = DB::table('chatbot_sessions')
                                ->insertGetId([
                                    'session_key' => $senderId,
                                    'ip_address' => 'webhook',
                                    'user_agent' => $channel,
                                    'started_at' => now(),
                                    'last_active_at' => now(),
                                ]);
                        } else {
                            DB::table('chatbot_sessions')
                                ->where('id', $sessionId)
                                ->update([
                                    'last_active_at' => now(),
                                ]);
                        }

                        $ticketCode =
                            strtoupper(substr($channel, 0, 2))
                            . now()->format('Ymd')
                            . '-'
                            . strtoupper(
                                \Illuminate\Support\Str::random(4)
                            );

                        DB::table('chatbot_tickets')->insert([
                            'session_id' => $sessionId,
                            'ticket_code' => $ticketCode,
                            'question' => "[$channel] {$ragQuestion}",
                            'bot_note' =>
                                'Messenger tự động chuyển nhân viên tư vấn '
                                . 'do câu trả lời không đủ căn cứ từ dữ liệu.',
                            'status' => 'pending',
                            'staff_answer' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $ragResult['ticket_code'] = $ticketCode;
                    }

                    /*
                     * Hỏi khéo léo thông tin liên hệ sau khi đã tư vấn một lúc:
                     * - chỉ Messenger;
                     * - RAG đã trả lời bình thường, không handoff;
                     * - Lead chưa có cả SĐT lẫn Email;
                     * - đã có ít nhất 2 tin nhắn inbound;
                     * - chỉ hỏi đúng một lần.
                     *
                     * Nếu thí sinh gửi SĐT/Email ở tin nhắn sau thì
                     * extractLeadInfo() hiện có sẽ tự cập nhật admission_leads.
                     */
                    if (
                        $isMessengerChat
                        && !$needsHandoff
                        && $answerText !== ''
                    ) {
                        $contactLead = DB::table('admission_leads')
                            ->where('id', $leadId)
                            ->first();

                        if ($contactLead) {
                            $contactProfile = json_decode(
                                $contactLead->profile ?? '{}',
                                true
                            );

                            if (!is_array($contactProfile)) {
                                $contactProfile = [];
                            }

                            $chatCount = DB::table('admission_lead_activities')
                                ->where('lead_id', $leadId)
                                ->where('channel', 'facebook')
                                ->where('type', 'chat')
                                ->where('direction', 'inbound')
                                ->count();

                            $missingContact =
                                trim((string) ($contactLead->phone ?? '')) === ''
                                && trim((string) ($contactLead->email ?? '')) === '';

                            $contactPromptShown =
                                !empty($contactProfile['contact_prompt_shown']);

                            if (
                                $missingContact
                                && !$contactPromptShown
                                && $chatCount >= 2
                            ) {
                                $answerText .= "\n\n"
                                    . 'Nếu bạn muốn cán bộ tuyển sinh hỗ trợ thêm '
                                    . 'khi cần, bạn có thể để lại số điện thoại '
                                    . 'hoặc Email nhé. Thông tin này là tùy chọn.';

                                $ragResult['answer'] = $answerText;

                                $contactProfile['contact_prompt_shown'] = true;
                                $contactProfile['contact_prompt_shown_at'] =
                                    now()->toIso8601String();

                                DB::table('admission_leads')
                                    ->where('id', $leadId)
                                    ->update([
                                        'profile' => json_encode(
                                            $contactProfile,
                                            JSON_UNESCAPED_UNICODE
                                        ),
                                        'updated_at' => now(),
                                    ]);
                            }
                        }
                    }

                    /*
                     * Lưu đúng nội dung thực tế đã gửi ra kênh.
                     * Nếu handoff thì lưu câu thông báo chuyển tư vấn viên,
                     * không lưu câu fallback ban đầu của AI.
                     */
                    if ($answerText !== '') {
                        DB::table('admission_lead_activities')->insert([
                            'lead_id' => $leadId,
                            'channel' => $channel,
                            'type' => 'chat',
                            'direction' => 'outbound',
                            'content' => $answerText,
                            'occurred_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    /*
                     * Nếu RAG trả lời được thì không tạo chatbot_tickets.
                     * Ticket chỉ đại diện cho trường hợp cần nhân viên xử lý.
                     */
                }
            }

            return response()->json([
                'success' => true,
                'lead_id' => $leadId,
                'score' => $scoreData['lead_score'] ?? null,
                'score_grade' => isset($scoreData['lead_level'])
                    ? strtolower((string) $scoreData['lead_level'])
                    : null,
                'messenger_ready' => !empty($messengerPsid),
                'data' => $resumePendingQuestion !== ''
                    ? $ragResult
                    : ($onboardingResult ?? $ragResult),
            ]);
        } catch (\Throwable $e) {
            Log::error(
                '[AdmissionWebhookController] upsertLead error: '
                . $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Messenger chỉ dùng Get Started để chào và mở phiên tư vấn.
     *
     * Không ép thí sinh khai SĐT/Email/Họ tên/Ngành/Tỉnh ngay từ đầu.
     * Các thông tin xuất hiện tự nhiên trong hội thoại vẫn được
     * extractLeadInfo() phía trên tự động trích xuất và cập nhật Lead.
     *
     * Sau một vài lượt chat, nếu Lead vẫn chưa có SĐT/Email thì phần RAG
     * phía trên sẽ khéo léo mời thí sinh để lại thông tin liên hệ một lần.
     */
    private function handleMessengerOnboarding(
        $leadId,
        $leadWasExisting,
        $channel,
        $interactionType,
        $content
    ) {
        if ($channel !== 'facebook') {
            return null;
        }

        if ($interactionType === 'get_started') {
            $this->logMessengerOnboardingActivity(
                $leadId,
                'get_started',
                $leadWasExisting
                    ? 'Người dùng mở lại tư vấn Messenger.'
                    : 'Người dùng bắt đầu tư vấn trên Messenger.'
            );

            return [
                'answer' =>
                    'Xin chào 👋 Mình là Trợ lý tư vấn tuyển sinh CTUT. '
                    . 'Bạn có thể hỏi mình về ngành học, phương thức xét tuyển, '
                    . 'học phí, hồ sơ hoặc các thông tin tuyển sinh khác nhé. '
                    . 'Bạn đang quan tâm nội dung nào?',
                'sources' => [],
                'generated' => false,
                'handoff' => false,
                'onboarding' => false,
                'onboarding_status' => 'completed',
                'onboarding_step' => null,
            ];
        }

        /*
         * Tin nhắn chat bình thường đi thẳng xuống luồng hiện có:
         * lưu activity -> extractLeadInfo() -> scoring -> RAG.
         */
        return null;
    }

    private function advanceMessengerOnboarding(
        $leadId,
        array $profile,
        array $state,
        $nextStep,
        $prefix = ''
    ) {
        if ($nextStep !== null) {
            $state['step'] = $nextStep;
            $profile['messenger_onboarding'] = $state;
            $this->saveMessengerProfile($leadId, $profile);

            return $this->messengerOnboardingPayload(
                $prefix . $this->messengerOnboardingQuestion($nextStep),
                'in_progress',
                $nextStep
            );
        }

        $resumeQuestion = trim((string) ($state['first_message'] ?? ''));
        unset($state['first_message']);

        $state['status'] = 'completed';
        $state['step'] = null;
        $state['completed_at'] = now()->toIso8601String();
        $profile['messenger_onboarding'] = $state;
        $this->saveMessengerProfile($leadId, $profile);

        $this->logMessengerOnboardingActivity(
            $leadId,
            'onboarding_completed',
            'Hoàn tất thu thập thông tin thí sinh trên Messenger.'
        );

        return $this->messengerOnboardingPayload(
            'Cảm ơn bạn 😊 Mình đã ghi nhận những thông tin cần thiết để '
            . 'hỗ trợ tư vấn phù hợp hơn. Bạn có thể tiếp tục hỏi mình về '
            . 'ngành học, phương thức xét tuyển, học phí, hồ sơ hoặc các '
            . 'thông tin tuyển sinh khác của CTUT nhé.',
            'completed',
            null,
            $resumeQuestion !== ''
                ? ['resume_question' => $resumeQuestion]
                : []
        );
    }

    private function nextMessengerOnboardingStep($lead, array $state = [])
    {
        if (!$lead) {
            return 'ask_name';
        }

        if (trim((string) ($lead->full_name ?? '')) === '') {
            return 'ask_name';
        }

        if (
            trim((string) ($lead->intended_major ?? '')) === ''
            && empty($state['skipped']['intended_major'])
        ) {
            return 'ask_major';
        }

        if (
            trim((string) ($lead->province ?? '')) === ''
            && empty($state['skipped']['province'])
        ) {
            return 'ask_province';
        }

        return null;
    }

    private function findLeadByContact($phone, $email, $excludeLeadId = null)
    {
        if ($phone) {
            $query = DB::table('admission_leads')->where('phone', $phone);
            if ($excludeLeadId) {
                $query->where('id', '<>', $excludeLeadId);
            }
            $lead = $query->first();
            if ($lead) {
                return $lead;
            }
        }

        if ($email) {
            $query = DB::table('admission_leads')->whereRaw(
                'LOWER(email) = ?',
                [mb_strtolower($email, 'UTF-8')]
            );
            if ($excludeLeadId) {
                $query->where('id', '<>', $excludeLeadId);
            }
            $lead = $query->first();
            if ($lead) {
                return $lead;
            }
        }

        return null;
    }

    private function mergeMessengerLeadIntoExistingLead(
        $sourceLeadId,
        $targetLeadId,
        $phone = null,
        $email = null
    ) {
        if ($sourceLeadId === $targetLeadId) {
            return $targetLeadId;
        }

        return DB::transaction(function () use (
            $sourceLeadId,
            $targetLeadId,
            $phone,
            $email
        ) {
            $source = DB::table('admission_leads')
                ->where('id', $sourceLeadId)
                ->lockForUpdate()
                ->first();
            $target = DB::table('admission_leads')
                ->where('id', $targetLeadId)
                ->lockForUpdate()
                ->first();

            if (!$source || !$target) {
                return $targetLeadId;
            }

            $sourceProfile = json_decode($source->profile ?? '{}', true);
            $targetProfile = json_decode($target->profile ?? '{}', true);
            if (!is_array($sourceProfile)) {
                $sourceProfile = [];
            }
            if (!is_array($targetProfile)) {
                $targetProfile = [];
            }

            /*
             * Giữ dữ liệu Website/Chatbot làm nền, bổ sung định danh và state
             * Messenger từ hồ sơ tạm. Không làm mất custom/scoring metadata cũ.
             */
            $mergedProfile = array_replace_recursive(
                $targetProfile,
                $sourceProfile
            );
            $mergedProfile['merged_channels']['facebook'] = true;
            $mergedProfile['merged_at'] = now()->toIso8601String();

            $updates = [
                'full_name' => $target->full_name ?: $source->full_name,
                'phone' => $target->phone ?: ($phone ?: $source->phone),
                'email' => $target->email ?: ($email ?: $source->email),
                'intended_major' => $target->intended_major
                    ?: $source->intended_major,
                'province' => $target->province ?: $source->province,
                'profile' => json_encode(
                    $mergedProfile,
                    JSON_UNESCAPED_UNICODE
                ),
                'last_interaction_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('admission_leads')
                ->where('id', $targetLeadId)
                ->update($updates);

            DB::table('admission_lead_channel_accounts')
                ->where('lead_id', $sourceLeadId)
                ->update([
                    'lead_id' => $targetLeadId,
                    'updated_at' => now(),
                ]);

            DB::table('admission_lead_activities')
                ->where('lead_id', $sourceLeadId)
                ->update(['lead_id' => $targetLeadId]);

            if (DB::getSchemaBuilder()->hasTable('admission_lead_score_logs')) {
                DB::table('admission_lead_score_logs')
                    ->where('lead_id', $sourceLeadId)
                    ->update(['lead_id' => $targetLeadId]);
            }

            DB::table('admission_leads')
                ->where('id', $sourceLeadId)
                ->delete();

            return $targetLeadId;
        });
    }

    private function normalizeMessengerPhone($value)
    {
        $value = (string) $value;

        if (!preg_match('/(?:\+?84|0)[0-9 .\-]{8,13}/', $value, $matches)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $matches[0]);

        /* Cho phép +84xxxxxxxxx và chuẩn hóa về 0xxxxxxxxx. */
        if (strpos($digits, '84') === 0 && strlen($digits) === 11) {
            $digits = '0' . substr($digits, 2);
        }

        return preg_match('/^0[0-9]{9}$/', $digits)
            ? $digits
            : null;
    }

    private function normalizeMessengerEmail($value)
    {
        if (
            preg_match(
                '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
                (string) $value,
                $matches
            )
        ) {
            $email = mb_strtolower(trim($matches[0]), 'UTF-8');
            return filter_var($email, FILTER_VALIDATE_EMAIL)
                ? $email
                : null;
        }

        return null;
    }

    private function messengerOnboardingQuestion($step)
    {
        switch ($step) {
            case 'ask_name':
                return 'Mình nên xưng hô với bạn như thế nào? Bạn cho mình xin '
                    . 'họ tên nhé.';

            case 'ask_major':
                return 'Để mình ưu tiên đúng nội dung bạn quan tâm, bạn đang tìm '
                    . 'hiểu ngành hoặc lĩnh vực nào của CTUT?';

            case 'ask_province':
                return 'Bạn đang học hoặc sinh sống tại tỉnh/thành nào? Thông tin '
                    . 'này giúp bộ phận tuyển sinh hỗ trợ phù hợp hơn; nếu chưa '
                    . 'tiện, bạn có thể nhắn “Bỏ qua”.';

            case 'ask_contact':
            default:
                return 'Để tránh tạo trùng hồ sơ và giúp mình nhận ra nếu bạn đã '
                    . 'từng đăng ký tư vấn trước đây, bạn cho mình xin số điện '
                    . 'thoại hoặc Email đã dùng nhé. Nếu chưa tiện chia sẻ, bạn '
                    . 'có thể nhắn “Bỏ qua”.';
        }
    }

    private function messengerOnboardingPayload(
        $answer,
        $status,
        $step = null,
        array $extra = []
    ) {
        return array_merge([
            'answer' => $answer,
            'sources' => [],
            'generated' => false,
            'handoff' => false,
            'onboarding' => true,
            'onboarding_status' => $status,
            'onboarding_step' => $step,
        ], $extra);
    }

    private function saveMessengerProfile($leadId, array $profile)
    {
        DB::table('admission_leads')
            ->where('id', $leadId)
            ->update([
                'profile' => json_encode(
                    $profile,
                    JSON_UNESCAPED_UNICODE
                ),
                'updated_at' => now(),
            ]);
    }

    private function logMessengerOnboardingActivity(
        $leadId,
        $type,
        $content
    ) {
        DB::table('admission_lead_activities')->insert([
            'lead_id' => $leadId,
            'channel' => 'facebook',
            'type' => $type,
            'external_id' => null,
            'direction' => 'inbound',
            'content' => $content,
            'payload' => json_encode([
                'source' => 'messenger_onboarding',
            ]),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function isMessengerSkipAnswer($value)
    {
        $value = mb_strtolower(
            trim((string) $value),
            'UTF-8'
        );

        return in_array($value, [
            'bỏ qua',
            'bo qua',
            'skip',
            'không',
            'khong',
            'chưa biết',
            'chua biet',
            'chưa xác định',
            'chua xac dinh',
        ], true);
    }

    private function messengerAnswerNeedsHandoff($answerText)
    {
        $text = mb_strtolower(
            trim(strip_tags((string) $answerText)),
            'UTF-8'
        );

        if ($text === '') {
            return false;
        }

        /*
         * Các mẫu này bám đúng kiểu fallback đang xuất hiện trong dữ liệu
         * Messenger hiện tại. Không bắt các câu chỉ yêu cầu làm rõ như
         * "câu hỏi chưa rõ" để tránh tạo ticket không cần thiết.
         */
        $markers = [
            'nằm ngoài thông tin',
            'không nằm trong thông tin',
            'không tìm thấy thông tin',
            'không tìm thấy dữ liệu',
            'không có thông tin cụ thể',
            'không có dữ liệu cụ thể',
            'tài liệu không nêu',
            'tài liệu chưa nêu',
            'không nêu rõ',
            'không đề cập',
            'bạn có muốn tôi chuyển',
            'chuyển câu hỏi này đến nhân viên tư vấn',
            'chuyển đến nhân viên tư vấn',
        ];

        foreach ($markers as $marker) {
            if (mb_strpos($text, $marker, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }

    public function ragAnswer(Request $request)
    {
        $question = $request->input('question');
        $ragService = app(\App\Services\AdmissionRagService::class);
        $result = $ragService->answer($question);
        
        return response()->json([
            'success' => true,
            'data' => [
                'answer' => $result['answer']
            ]
        ]);
    }

    public function checkLeadStatus($id)
    {
        $lead = DB::table('admission_leads')->where('id', $id)->first();
        
        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $lead->id,
                'status' => $lead->status, // e.g. 'new', 'contacted', 'qualified'
                'last_interaction_at' => $lead->last_interaction_at,
                'score_grade' => $lead->score_grade,
                'is_contacted' => $lead->status !== 'new',
            ]
        ]);
    }

    public function notification(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Da ghi nhan su kien thong bao tu n8n.',
        ]);
    }

    public function reportToxicComment(Request $request)
{
    $data = $request->validate([
        'platform' => 'nullable|string|max:50',
        'comment_id' => 'required|string|max:255',
        'post_id' => 'nullable|string|max:255',
        'post_message' => 'nullable|string',
        'post_url' => 'nullable|string|max:1000',
        'sender_id' => 'nullable|string|max:255',
        'sender_name' => 'nullable|string|max:255',
        'message' => 'required|string',
        'sentiment_category' => 'required|string|max:100',
        'ai_reason' => 'nullable|string',

        'facebook_hide_status' =>
            'nullable|in:hidden,failed,unknown',
        'facebook_hidden' => 'nullable',
        'facebook_can_hide' => 'nullable',
        'facebook_hide_error' => 'nullable|string',
        'facebook_hide_attempted_at' => 'nullable|date',
    ]);

    $toNullableBoolean = function ($value) {
        if (
            $value === null
            || $value === ''
            || $value === '?'
        ) {
            return null;
        }

        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
    };

    $facebookHidden = $toNullableBoolean(
        $request->input('facebook_hidden')
    );

    $facebookCanHide = $toNullableBoolean(
        $request->input('facebook_can_hide')
    );

    /*
     * MySQL DATETIME/TIMESTAMP không nhận trực tiếp dạng:
     * 2026-08-05T15:08:51.034Z
     *
     * Chuẩn hóa về:
     * 2026-08-05 22:08:51
     */
    $facebookHideAttemptedAt = now();

    if (!empty($data['facebook_hide_attempted_at'])) {
        try {
            $facebookHideAttemptedAt =
                \Carbon\Carbon::parse(
                    $data['facebook_hide_attempted_at']
                )
                ->setTimezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            Log::warning(
                '[AdmissionWebhookController] '
                . 'facebook_hide_attempted_at không hợp lệ: '
                . $data['facebook_hide_attempted_at']
            );

            $facebookHideAttemptedAt = now();
        }
    }

    try {
        $commentData = [
            'platform' => $data['platform'] ?? 'facebook',
            'post_id' => $data['post_id'] ?? null,
            'post_message' =>
                $data['post_message'] ?? null,
            'post_url' => $data['post_url'] ?? null,
            'sender_id' => $data['sender_id'] ?? null,
            'sender_name' =>
                $data['sender_name'] ?? 'Unknown User',
            'message' => $data['message'],
            'sentiment_category' =>
                $data['sentiment_category'],
            'ai_reason' => $data['ai_reason'] ?? null,

            'facebook_hide_status' =>
                $data['facebook_hide_status']
                ?? 'unknown',
            'facebook_hidden' => $facebookHidden,
            'facebook_can_hide' => $facebookCanHide,
            'facebook_hide_error' =>
                $data['facebook_hide_error'] ?? null,
            'facebook_hide_attempted_at' =>
                $facebookHideAttemptedAt,

            'updated_at' => now(),
        ];

        $existing = DB::table(
            'social_toxic_comments'
        )
            ->where(
                'platform',
                $commentData['platform']
            )
            ->where(
                'comment_id',
                $data['comment_id']
            )
            ->first();

        if ($existing) {
            /*
             * Không đặt lại trạng thái xử lý của Admin.
             * Chỉ cập nhật nội dung và kết quả thao tác Facebook.
             */
            DB::table('social_toxic_comments')
                ->where('id', $existing->id)
                ->update($commentData);

            $id = $existing->id;
        } else {
            $commentData['comment_id'] =
                $data['comment_id'];
            $commentData['status'] = 'pending';
            $commentData['created_at'] = now();

            $id = DB::table(
                'social_toxic_comments'
            )->insertGetId($commentData);
        }

        return response()->json([
            'success' => true,
            'id' => $id,
            'message' =>
                $commentData['facebook_hide_status']
                    === 'hidden'
                    ? 'Đã ẩn và ghi nhận bình luận vi phạm.'
                    : 'Đã ghi nhận bình luận vi phạm, '
                        . 'nhưng Facebook chưa ẩn được.',
            'facebook_hide_status' =>
                $commentData['facebook_hide_status'],
        ]);
    } catch (\Throwable $e) {
        Log::error(
            '[AdmissionWebhookController] '
            . 'reportToxicComment error: '
            . $e->getMessage()
        );

        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}


    public function storeSocialPost(Request $request)
    {
        try {
            DB::table('social_posts')->insert([
                'title' => $request->input('title', 'Bài viết tự động'),
                'content' => $request->input('content', ''),
                'image_url' => $request->input('image_url'),
                'platforms' => json_encode($request->input('platforms', ['facebook'])),
                'status' => 'published',
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Lưu bài viết thành công']);
        } catch (\Throwable $e) {
            Log::error('[AdmissionWebhookController] storeSocialPost error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function callback(Request $request)
    {
        Log::info('[AdmissionWebhookController] n8n callback received:', $request->all());
        return response()->json([
            'success' => true,
            'message' => 'Callback processed',
        ]);
    }

    public function log(Request $request)
{
    try {
        $workflow = trim(
            (string) $request->input(
                'workflow',
                'Unknown Workflow'
            )
        );

        $eventType = trim(
            (string) $request->input(
                'event_type',
                'system_log'
            )
        );

        $status = strtolower(
            trim(
                (string) $request->input(
                    'status',
                    'received'
                )
            )
        );

        $message = trim(
            (string) $request->input(
                'message',
                'Không có nội dung.'
            )
        );

        DB::table('admission_n8n_logs')->insert([
            'workflow' => $workflow,
            'event_type' => $eventType,
            'status' => $status,
            'message' => $message,

            'payload' => json_encode(
                $request->all(),
                JSON_UNESCAPED_UNICODE
            ),

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã ghi nhận log n8n.',
        ]);
    } catch (\Throwable $e) {
        Log::error(
            '[AdmissionWebhookController] n8n log error: '
            . $e->getMessage()
        );

        return response()->json([
            'success' => false,
            'message' => 'Không ghi được log n8n.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}