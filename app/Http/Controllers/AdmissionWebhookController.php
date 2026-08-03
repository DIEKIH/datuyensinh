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
            
            // 0. Log webhook payload
            DB::table('admission_n8n_logs')->insert([
                'event_type' => 'webhook_upsert_lead',
                'workflow' => 'messenger_flow',
                'status' => 'received',
                'message' => 'Received webhook from n8n',
                'payload' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $channel = $request->input('channel', 'facebook');
            $content = $request->input('question', $request->input('content', ''));
            $mediaUrl = $request->input('media_url');
            $senderId = $request->input('sender_id');
            $isComment = $request->input('is_comment', false);

            // 1. Tìm lead qua bảng tài khoản đa kênh (channel + account_id)
            $leadId = null;
            $lead = null;
            $account = null;

            if ($senderId) {
                $account = DB::table('admission_lead_channel_accounts')
                    ->where('channel', $channel)
                    ->where('account_id', $senderId)
                    ->first();

                if ($account) {
                    $leadId = $account->lead_id;
                    $lead = DB::table('admission_leads')->where('id', $leadId)->first();
                } else {
                    // Fallback cho lead cũ trước khi có bảng account
                    $lead = DB::table('admission_leads')->where('profile->sender_id', $senderId)->first();
                    if ($lead) $leadId = $lead->id;
                }
            }

            if (!$lead && $senderId) {
                $leadId = DB::table('admission_leads')->insertGetId([
                    'channel' => $channel,
                    'profile' => json_encode(['sender_id' => $senderId]),
                    'status' => 'new',
                    'last_interaction_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $lead = DB::table('admission_leads')->where('id', $leadId)->first();
            }

            // Lưu/refresh tài khoản tương tác của kênh này (fb sender_id / zalo uid...)
            if ($leadId && $senderId && !$account) {
                DB::table('admission_lead_channel_accounts')->insert([
                    'lead_id' => $leadId,
                    'channel' => $channel,
                    'account_id' => $senderId,
                    'last_interaction_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($account) {
                DB::table('admission_lead_channel_accounts')->where('id', $account->id)
                    ->update(['last_interaction_at' => now(), 'updated_at' => now()]);
            }

            // 2. Insert activity
            if ($leadId && $content) {
                $type = $isComment ? 'comment' : 'chat';
                DB::table('admission_lead_activities')->insert([
                    'lead_id' => $leadId,
                    'channel' => $channel,
                    'type' => $type,
                    'external_id' => $request->input('comment_id'), // if it's a comment
                    'direction' => 'inbound',
                    'content' => $content,
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // 2.5 AI Extract Profile
                $extracted = $this->rag->extractLeadInfo($content);
                if (!empty($extracted)) {
                    $profile = json_decode($lead->profile ?? '{}', true) ?? [];
                    $updated = false;
                    foreach (['phone', 'email', 'major', 'intent'] as $key) {
                        if (!empty($extracted[$key])) {
                            $profile[$key] = $extracted[$key];
                            $updated = true;
                        }
                    }
                    if ($updated) {
                        $encodedProfile = json_encode($profile);
                        DB::table('admission_leads')->where('id', $leadId)->update(['profile' => $encodedProfile]);
                        $lead->profile = $encodedProfile;
                    }
                }
                
                // 3. Rescore lead
                $activities = DB::table('admission_lead_activities')
                    ->where('lead_id', $leadId)
                    ->get()
                    ->map(function($a) { return (array)$a; })
                    ->toArray();
                    
                $scoreData = $this->scoring->score((array)$lead, $activities);
                
                DB::table('admission_leads')->where('id', $leadId)->update([
                    'score' => $scoreData['lead_score'] ?? 0,
                    'score_grade' => $scoreData['lead_level'] ?? 'Cold',
                    'last_interaction_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // 4. Call RAG or Human Handoff
            $ragResult = null;
            if (!empty($content) || !empty($mediaUrl)) {
                
                // KIỂM TRA: Có đang đợi Nhân viên thật trả lời không? (Livechat Handoff)
                $sessionId = DB::table('chatbot_sessions')->where('session_key', $senderId)->value('id');
                $isPending = false;
                if ($sessionId) {
                    $hasPendingTicket = DB::table('chatbot_tickets')
                        ->where('session_id', $sessionId)
                        ->where('status', 'pending')
                        ->exists();
                    if ($hasPendingTicket) {
                        $isPending = true;
                    }
                }

                if ($isPending) {
                    // Cướp quyền Bot: Không gọi RAG nữa, trả về tin nhắn giữ chân khách
                    $ragResult = [
                        'answer' => 'Tin nhắn của bạn đã được chuyển đến Thầy/Cô tư vấn viên. Thầy/Cô sẽ phản hồi bạn ngay, bạn đợi một chút nhé!',
                        'sources' => [],
                        'generated' => false,
                    ];
                    
                    // Cập nhật câu hỏi mới vào ticket đang pending
                    DB::table('chatbot_tickets')
                        ->where('session_id', $sessionId)
                        ->where('status', 'pending')
                        ->update([
                            'question' => DB::raw("CONCAT(question, '\n\n[Tin nhắn mới]: ', " . DB::getPdo()->quote($content) . ")"),
                            'updated_at' => now()
                        ]);
                } else {
                    // Gọi RAG bình thường
                    $ragResult = $this->rag->answer($content, $senderId, $mediaUrl);
                    
                    // SAVE BOT RESPONSE TO ACTIVITIES
                    if ($leadId && isset($ragResult['answer']) && $ragResult['answer'] !== '') {
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
                    
                    // CREATE TICKET cho Giao diện Quản trị
                    $answerText = $ragResult['answer'] ?? '';
                    $isFallback = strpos($answerText, 'nằm ngoài thông tin') !== false || strpos($answerText, 'để lại Tên và') !== false;
                    
                    if (!$sessionId) {
                        $sessionId = DB::table('chatbot_sessions')->insertGetId([
                            'session_key' => $senderId,
                            'ip_address' => 'webhook',
                            'user_agent' => $channel,
                            'started_at' => now(),
                            'last_active_at' => now(),
                        ]);
                    } else {
                        DB::table('chatbot_sessions')->where('id', $sessionId)->update(['last_active_at' => now()]);
                    }
                    
                    $ticketCode = strtoupper(substr($channel, 0, 2)) . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
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
                'data' => $ragResult,
            ]);
        } catch (\Throwable $e) {
            Log::error('[AdmissionWebhookController] upsertLead error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
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
