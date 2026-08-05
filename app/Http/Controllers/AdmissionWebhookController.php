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

            if (!$lead && $senderId !== '') {
                $profile = [
                    'sender_id' => $senderId,
                ];

                if ($senderName !== '') {
                    $profile['full_name'] = $senderName;
                }

                // Chỉ sender_id nhận từ sự kiện chat Messenger mới được xem là PSID.
                if ($channel === 'facebook' && $interactionType === 'chat') {
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
                && $interactionType === 'chat'
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

            if (
                !in_array($interactionType, ['like', 'share'], true)
                && ($content !== '' || !empty($mediaUrl))
            ) {
                $sessionId = DB::table('chatbot_sessions')
                    ->where('session_key', $senderId)
                    ->value('id');
                $isPending = false;

                if ($sessionId) {
                    $isPending = DB::table('chatbot_tickets')
                        ->where('session_id', $sessionId)
                        ->where('status', 'pending')
                        ->exists();
                }

                if ($isPending) {
                    $ragResult = [
                        'answer' => 'Tin nhắn của bạn đã được chuyển đến Thầy/Cô tư vấn viên. Thầy/Cô sẽ phản hồi bạn ngay, bạn đợi một chút nhé!',
                        'sources' => [],
                        'generated' => false,
                    ];

                    DB::table('chatbot_tickets')
                        ->where('session_id', $sessionId)
                        ->where('status', 'pending')
                        ->update([
                            'question' => DB::raw(
                                "CONCAT(question, '\n\n[Tin nhắn mới]: ', "
                                . DB::getPdo()->quote($content)
                                . ')'
                            ),
                            'updated_at' => now(),
                        ]);
                } else {
                    $ragResult = $this->rag->answer(
                        $content,
                        $senderId,
                        $mediaUrl
                    );

                    if (
                        isset($ragResult['answer'])
                        && $ragResult['answer'] !== ''
                    ) {
                        DB::table('admission_lead_activities')->insert([
                            'lead_id' => $leadId,
                            'channel' => $channel,
                            'type' => 'chat',
                            'direction' => 'outbound',
                            'content' => $ragResult['answer'],
                            'occurred_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $answerText = $ragResult['answer'] ?? '';
                    $isFallback =
                        strpos($answerText, 'nằm ngoài thông tin') !== false
                        || strpos($answerText, 'để lại Tên và') !== false;

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

                    $ticketCode = strtoupper(substr($channel, 0, 2))
                        . now()->format('Ymd')
                        . '-'
                        . strtoupper(\Illuminate\Support\Str::random(4));

                    DB::table('chatbot_tickets')->insert([
                        'session_id' => $sessionId,
                        'ticket_code' => $ticketCode,
                        'question' => "[$channel] {$content}",
                        'status' => $isFallback ? 'pending' : 'answered',
                        'staff_answer' => $isFallback ? null : $answerText,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
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
                'data' => $ragResult,
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
        try {
            DB::table('social_toxic_comments')->insert([
                'platform' => $request->input('platform', 'facebook'),
                'comment_id' => $request->input('comment_id', ''),
                'post_id' => $request->input('post_id'),
                'sender_id' => $request->input('sender_id'),
                'sender_name' => $request->input('sender_name', 'Unknown User'),
                'message' => $request->input('message', ''),
                'sentiment_category' => $request->input('sentiment_category', 'toxic'),
                'ai_reason' => $request->input('ai_reason', ''),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Đã ghi nhận bình luận vi phạm']);
        } catch (\Throwable $e) {
            Log::error('[AdmissionWebhookController] reportToxicComment error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
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
        Log::info('[AdmissionWebhookController] n8n log:', $request->all());
        return response()->json([
            'success' => true,
            'message' => 'Log processed',
        ]);
    }
}