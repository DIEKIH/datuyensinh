<?php

namespace App\Http\Controllers;

use App\Services\AdmissionLeadScoringService;
use App\Services\AdmissionRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdmissionAdminController extends Controller
{
    private $scoring;
    private $rag;

    public function __construct(AdmissionLeadScoringService $scoring, AdmissionRagService $rag)
    {
        $this->scoring = $scoring;
        $this->rag = $rag;
    }

    public function index()
    {
        return view('admins.pages.admission.dashboard');
    }

    public function leadManagement()
    {
        return view('admins.pages.admission.leads');
    }

    public function approvalManagement()
    {
        return view('admins.pages.admission.approvals');
    }

    public function openAiManagement()
    {
        return view('admins.pages.admission.openai');
    }

    public function scoringManagement()
    {
        return view('admins.pages.admission.scoring');
    }

    public function toxicManagement()
    {
        return view('admins.pages.admission.toxic');
    }

    public function socialPostManagement()
    {
        return view('admins.pages.admission.social_posts');
    }

    public function n8nMonitoring()
    {
        return view('admins.pages.admission.n8n_logs');
    }


    public function campaignManagement()
    {
        return view('admins.pages.admission.campaigns');
    }

    public function triggerNurture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'campaign_name' => 'required|string|max:255',
            'minimum_score' => 'required|numeric|min:0|max:100',
            'email_subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'send_email' => 'nullable|boolean',
            'send_messenger' => 'nullable|boolean',
        ], [
            'campaign_name.required' => 'Vui lòng nhập tên chiến dịch.',
            'minimum_score.required' => 'Vui lòng nhập điểm tối thiểu.',
            'minimum_score.numeric' => 'Điểm tối thiểu phải là số.',
            'message.required' => 'Vui lòng nhập nội dung chiến dịch.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin chiến dịch chưa hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $sendEmail = in_array(
            $request->input('send_email'),
            [1, '1', true, 'true', 'on'],
            true
        );

        $sendMessenger = in_array(
            $request->input('send_messenger'),
            [1, '1', true, 'true', 'on'],
            true
        );

        if (!$sendEmail && !$sendMessenger) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn ít nhất một kênh gửi.',
                'errors' => [
                    'channels' => [
                        'Phải chọn Email hoặc Messenger.',
                    ],
                ],
            ], 422);
        }

        if (
            $sendEmail
            && trim((string) ($data['email_subject'] ?? '')) === ''
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập tiêu đề Email.',
                'errors' => [
                    'email_subject' => [
                        'Tiêu đề Email là bắt buộc khi chọn gửi Email.',
                    ],
                ],
            ], 422);
        }

        $minimumScore = (float) $data['minimum_score'];

        $leads = DB::table('admission_leads')
            ->select([
                'id',
                'full_name',
                'email',
                'intended_major',
                'score',
                'profile',
            ])
            ->where('score', '>=', $minimumScore)
            ->orderByDesc('score')
            ->orderByDesc('last_interaction_at')
            ->get();

        $recipients = [];
        $emailSeen = [];
        $messengerSeen = [];
        $emailCount = 0;
        $messengerCount = 0;

        foreach ($leads as $lead) {
            $fullName = trim((string) ($lead->full_name ?? ''));
            $intendedMajor = trim(
                (string) ($lead->intended_major ?? '')
            );
            $score = (float) ($lead->score ?? 0);

            if ($sendEmail) {
                $email = strtolower(trim((string) ($lead->email ?? '')));

                if (
                    $email !== ''
                    && filter_var($email, FILTER_VALIDATE_EMAIL)
                    && !isset($emailSeen[$email])
                ) {
                    $emailSeen[$email] = true;
                    $emailCount++;

                    $recipients[] = [
                        'lead_id' => (int) $lead->id,
                        'channel' => 'email',
                        'recipient' => $email,
                        'email' => $email,
                        'sender_id' => null,
                        'full_name' => $fullName,
                        'intended_major' => $intendedMajor,
                        'score' => $score,
                    ];
                }
            }

            if ($sendMessenger) {
                $profile = json_decode(
                    (string) ($lead->profile ?? ''),
                    true
                );

                if (!is_array($profile)) {
                    $profile = [];
                }

                /*
                 * Chỉ dùng messenger_psid đã được lưu từ sự kiện chat.
                 * Không dùng sender_id của Like/Share/Comment để tránh gửi sai.
                 */
                $messengerPsid = trim(
                    (string) ($profile['messenger_psid'] ?? '')
                );

                if (
                    $messengerPsid !== ''
                    && !isset($messengerSeen[$messengerPsid])
                ) {
                    $messengerSeen[$messengerPsid] = true;
                    $messengerCount++;

                    $recipients[] = [
                        'lead_id' => (int) $lead->id,
                        'channel' => 'messenger',
                        'recipient' => $messengerPsid,
                        'email' => null,
                        'sender_id' => $messengerPsid,
                        'full_name' => $fullName,
                        'intended_major' => $intendedMajor,
                        'score' => $score,
                    ];
                }
            }
        }

        if (empty($recipients)) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Không có lead đạt điều kiện và có kênh liên hệ phù hợp.',
                'data' => [
                    'candidate_count' => $leads->count(),
                    'email_count' => 0,
                    'messenger_count' => 0,
                ],
            ], 422);
        }

        $campaignId = 'CD'
            . now()->format('YmdHis')
            . '-'
            . strtoupper(
                \Illuminate\Support\Str::random(6)
            );

        $payload = [
            'event_type' => 'run_lead_campaign',
            'data' => [
                'campaign_id' => $campaignId,
                'campaign_name' => $data['campaign_name'],
                'campaign_mode' => 'manual',
                'minimum_score' => $minimumScore,
                'subject' => trim(
                    (string) ($data['email_subject'] ?? '')
                ),
                'message' => trim((string) $data['message']),
                'recipients' => $recipients,
            ],
        ];

        $n8nUrl = env(
            'N8N_MASTER_WEBHOOK_URL',
            'http://localhost:5678/webhook/master-receiver'
        );

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(30)
                ->post($n8nUrl, $payload);

            if (!$response->successful()) {
                $this->writeCampaignLog(
                    'failed',
                    'n8n trả về HTTP ' . $response->status(),
                    $payload
                );

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Không thể chuyển chiến dịch sang n8n.',
                    'n8n_status' => $response->status(),
                    'n8n_response' => $response->body(),
                ], 502);
            }

            $this->writeCampaignLog(
                'sent',
                'Đã chuyển chiến dịch sang n8n.',
                $payload
            );

            return response()->json([
                'success' => true,
                'message' =>
                    'Đã chuyển chiến dịch sang n8n để gửi Email và Messenger.',
                'data' => [
                    'campaign_id' => $campaignId,
                    'candidate_count' => $leads->count(),
                    'recipient_count' => count($recipients),
                    'email_count' => $emailCount,
                    'messenger_count' => $messengerCount,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->writeCampaignLog(
                'failed',
                $e->getMessage(),
                $payload
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Lỗi kết nối n8n: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function writeCampaignLog(
        $status,
        $message,
        array $payload
    ) {
        try {
            DB::table('admission_n8n_logs')->insert([
                'event_type' => 'run_lead_campaign',
                'workflow' => 'master_campaign',
                'status' => $status,
                'message' => $message,
                'payload' => json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                '[Campaign] Không ghi được admission_n8n_logs: '
                . $e->getMessage()
            );
        }
    }

    public function listApprovals()
    {
        $approvals = \App\Models\AiApproval::where('status', 'pending')
                        ->orderBy('created_at', 'desc')
                        ->get();
        return response()->json(['success' => true, 'data' => $approvals]);
    }

    public function handleApprovalAction(Request $request, $id)
    {
        $approval = \App\Models\AiApproval::findOrFail($id);
        $action = $request->input('action'); // 'approve', 'rewrite', 'reject'
        $newAnswer = $request->input('ai_answer');
        $feedback = $request->input('admin_feedback');

        if ($action === 'approve') {
            $approval->status = 'approved';
            $approval->ai_answer = $newAnswer ?: $approval->ai_answer;
            $approval->save();

            if ($approval->channel === 'email' && $approval->customer_email) {
                try {
                    $n8nUrl = env('N8N_EMAIL_WEBHOOK_URL', 'http://localhost:5678/webhook/send-approved-email');
                    $response = \Illuminate\Support\Facades\Http::withoutVerifying()->post($n8nUrl, [
                        'event_type' => 'send_approved_email',
                        'data' => [
                            'email' => $approval->customer_email,
                            'subject' => '[CTUT] Trả lời thắc mắc của bạn',
                            'body' => $approval->ai_answer
                        ]
                    ]);
                    \Illuminate\Support\Facades\Log::info("Gui n8n webhook approve: " . $response->status() . " " . $response->body());

                    if (!$response->successful()) {
                        return response()->json(['success' => false, 'message' => 'Gửi n8n thất bại: ' . $response->body()], 500);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error sending approved email via n8n: ' . $e->getMessage());
                    return response()->json(['success' => false, 'message' => 'Lỗi kết nối n8n: ' . $e->getMessage()], 500);
                }
            } else {
                // MỚI THÊM: báo rõ ràng khi thiếu email, không gửi được
                return response()->json([
                    'success' => true,
                    'message' => 'Đã duyệt nhưng KHÔNG gửi được mail — khách hàng chưa có email liên hệ.'
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Đã duyệt và gửi nội dung thành công!']);
        } elseif ($action === 'rewrite') {
            $approval->admin_feedback = $feedback;
            $prompt = "Câu hỏi ban đầu: {$approval->question}\n\nLưu ý từ Admin để sửa lại: {$feedback}\n\nHãy viết lại câu trả lời thật chính xác.";
            try {
                $ragService = app(\App\Services\AdmissionRagService::class);
                $result = $ragService->answer($prompt);
                $approval->ai_answer = $result['answer'] ?? "Lỗi AI";
            } catch (\Exception $e) {
                // Ignore for now
            }
            $approval->save();
            return response()->json(['success' => true, 'message' => 'Đã gửi yêu cầu AI viết lại!', 'new_answer' => $approval->ai_answer]);

        } elseif ($action === 'reject') {
            $approval->status = 'rejected';
            $approval->save();
            return response()->json(['success' => true, 'message' => 'Đã từ chối câu trả lời này.']);
        }

        return response()->json(['success' => false, 'message' => 'Hành động không hợp lệ.']);
    }

    public function listToxicComments(Request $request)
    {
        $query = DB::table('social_toxic_comments')->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $comments = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    public function storeToxicCommentWebhook(Request $request)
    {
        $configuredSecret = trim((string) env('N8N_TO_LARAVEL_SECRET', ''));
        $receivedSecret = trim((string) $request->header('X-Webhook-Secret', ''));

        if (
            $configuredSecret === ''
            || $receivedSecret === ''
            || !hash_equals($configuredSecret, $receivedSecret)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook không hợp lệ.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|max:40',
            'comment_id' => 'required|string|max:255',
            'post_id' => 'nullable|string|max:255',
            'sender_id' => 'nullable|string|max:255',
            'sender_name' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:10000',
            'sentiment_category' => 'nullable|string|max:100',
            'ai_reason' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu bình luận chưa hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $existingId = DB::table('social_toxic_comments')
            ->where('platform', $data['platform'])
            ->where('comment_id', $data['comment_id'])
            ->value('id');

        if ($existingId) {
            return response()->json([
                'success' => true,
                'message' => 'Bình luận đã được ghi nhận trước đó.',
                'data' => ['id' => (int) $existingId],
            ]);
        }

        $id = DB::table('social_toxic_comments')->insertGetId([
            'platform' => $data['platform'],
            'comment_id' => $data['comment_id'],
            'post_id' => $data['post_id'] ?? null,
            'sender_id' => $data['sender_id'] ?? null,
            'sender_name' => $data['sender_name'] ?? null,
            'message' => $data['message'] ?? null,
            'sentiment_category' => $data['sentiment_category'] ?? 'toxic',
            'ai_reason' => $data['ai_reason'] ?? null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu bình luận chờ Admin xử lý.',
            'data' => ['id' => (int) $id],
        ]);
    }

    public function handleToxicCommentAction(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:ignore,delete',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Hành động không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment = DB::table('social_toxic_comments')
            ->where('id', $id)
            ->first();

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bình luận.',
            ], 404);
        }

        if ($comment->platform !== 'facebook') {
            return response()->json([
                'success' => false,
                'message' => 'Nền tảng này chưa hỗ trợ thao tác bình luận.',
            ], 422);
        }

        $adminAction = $validator->validated()['action'];
        $n8nAction = $adminAction === 'ignore' ? 'unhide' : 'delete';
        $newStatus = $adminAction === 'ignore' ? 'ignored' : 'deleted';

        $payload = [
            'event_type' => 'toxic_comment_action',
            'data' => [
                'record_id' => (int) $comment->id,
                'action' => $n8nAction,
                'comment_id' => (string) $comment->comment_id,
                'post_id' => (string) ($comment->post_id ?? ''),
                'platform' => (string) $comment->platform,
            ],
        ];

        $n8nUrl = env(
            'N8N_MASTER_WEBHOOK_URL',
            'http://localhost:5678/webhook/master-receiver'
        );

        try {
            $http = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->acceptJson()
                ->timeout(30);

            $webhookSecret = trim((string) env('N8N_WEBHOOK_SECRET', ''));

            if ($webhookSecret !== '') {
                $http = $http->withHeaders([
                    'X-Webhook-Secret' => $webhookSecret,
                ]);
            }

            $response = $http->post($n8nUrl, $payload);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::error(
                    'N8N Toxic Comment Action HTTP Error',
                    [
                        'status' => $response->status(),
                        'response' => $response->body(),
                        'payload' => $payload,
                    ]
                );

                return response()->json([
                    'success' => false,
                    'message' => 'n8n chưa xử lý được bình luận trên Facebook.',
                ], 502);
            }

            $responseData = $response->json();

            if (
                is_array($responseData)
                && array_key_exists('success', $responseData)
                && $responseData['success'] === false
            ) {
                return response()->json([
                    'success' => false,
                    'message' => $responseData['message']
                        ?? 'Facebook không chấp nhận thao tác bình luận.',
                ], 502);
            }

            DB::table('social_toxic_comments')
                ->where('id', $id)
                ->update([
                    'status' => $newStatus,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => $adminAction === 'ignore'
                    ? 'Đã bỏ ẩn bình luận trên Facebook.'
                    : 'Đã xóa bình luận trên Facebook.',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'N8N Toxic Comment Action Exception',
                [
                    'message' => $e->getMessage(),
                    'payload' => $payload,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Không kết nối được đến n8n.',
            ], 502);
        }
    }

    public function listSocialPosts(Request $request)
    {
        $posts = DB::table('social_posts')
            ->orderBy('published_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    public function stats()
    {
        $totalLeads = DB::table('admission_leads')->count();
        $hotLeads = DB::table('admission_leads')->where('score_grade', 'hot')->count();
        $warmLeads = DB::table('admission_leads')->where('score_grade', 'warm')->count();

        // Nguồn Traffic
        $sources = DB::table('admission_leads')
            ->select('channel', DB::raw('count(*) as total'))
            ->groupBy('channel')
            ->get();

        // Bot Deflection Rate
        $totalTickets = DB::table('chatbot_tickets')->count();
        $answeredTickets = DB::table('chatbot_tickets')->where('status', 'answered')->count();
        $pendingTickets = DB::table('chatbot_tickets')->where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_leads' => $totalLeads,
                'hot_leads' => $hotLeads,
                'new_leads' => DB::table('admission_leads')->where('status', 'new')->count(),
                'rag_documents' => DB::table('admission_rag_documents')->count(),
                'n8n_events' => DB::table('admission_n8n_logs')->whereDate('created_at', now()->toDateString())->count(),
                
                // Chart Data
                'funnel' => [
                    'total' => $totalLeads,
                    'warm' => $warmLeads,
                    'hot' => $hotLeads,
                ],
                'sources' => $sources,
                'tickets' => [
                    'total' => $totalTickets,
                    'answered' => $answeredTickets,
                    'pending' => $pendingTickets
                ]
            ],
        ]);
    }

    public function leads(Request $request)
    {
        $channelAccounts = DB::table('admission_lead_channel_accounts')
            ->select('lead_id')
            ->selectRaw(
                "MAX(CASE WHEN channel IN ('facebook', 'messenger') "
                . "THEN account_id END) AS facebook_account_id"
            )
            ->selectRaw(
                "MAX(CASE WHEN channel = 'zalo' "
                . "THEN account_id END) AS zalo_account_id"
            )
            ->groupBy('lead_id');

        $query = DB::table('admission_leads as leads')
            ->leftJoinSub(
                $channelAccounts,
                'channel_accounts',
                function ($join) {
                    $join->on(
                        'channel_accounts.lead_id',
                        '=',
                        'leads.id'
                    );
                }
            )
            ->select([
                'leads.*',
                'channel_accounts.facebook_account_id',
                'channel_accounts.zalo_account_id',
            ]);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where(
                    'leads.full_name',
                    'like',
                    '%' . $keyword . '%'
                )
                    ->orWhere(
                        'leads.phone',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhere(
                        'leads.email',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhere(
                        'leads.intended_major',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhere(
                        'channel_accounts.facebook_account_id',
                        'like',
                        '%' . $keyword . '%'
                    );
            });
        }

        foreach (['channel', 'status', 'score_grade'] as $filter) {
            if ($request->filled($filter)) {
                $query->where(
                    'leads.' . $filter,
                    $request->input($filter)
                );
            }
        }

        $leads = $query
            ->orderByDesc('leads.score')
            ->orderByDesc('leads.last_interaction_at')
            ->orderByDesc('leads.created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $leads,
        ]);
    }

    public function storeLead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'channel' => 'required|string|max:80',
            'source_campaign' => 'nullable|string|max:255',
            'intended_major' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:40',
            'note' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['status'] = $data['status'] ?? 'new';
        $data['last_interaction_at'] = now();
        $scored = $this->scoring->score($data);
        $data['score'] = $scored['lead_score'] ?? 0;
        $data['score_grade'] = strtolower(
            (string) ($scored['lead_level'] ?? 'cold')
        );
        $data['profile'] = json_encode([
            'score_reasons' => $scored['matched_criteria'] ?? [],
        ]);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('admission_leads')->insertGetId($data);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function showLead($id)
    {
        $lead = DB::table('admission_leads')->where('id', $id)->first();

        if (!$lead) {
            return response()->json(['success' => false, 'message' => 'Khong tim thay lead.'], 404);
        }

        $activities = DB::table('admission_lead_activities')
            ->where('lead_id', $id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at')
            ->get();

        $scoreLogs = DB::table('admission_lead_score_logs')
            ->leftJoin('admission_scoring_criteria', 'admission_lead_score_logs.criterion_id', '=', 'admission_scoring_criteria.id')
            ->where('admission_lead_score_logs.lead_id', $id)
            ->select('admission_lead_score_logs.*', 'admission_scoring_criteria.criterion_name', 'admission_scoring_criteria.criterion_code')
            ->orderByDesc('admission_lead_score_logs.created_at')
            ->get();

        return response()->json(['success' => true, 'data' => compact('lead', 'activities', 'scoreLogs')]);
    }

    public function updateLeadStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|max:40',
            'note' => 'nullable|string|max:5000',
        ]);

        $updated = DB::table('admission_leads')
            ->where('id', $id)
            ->update([
                'status' => $request->status,
                'note' => $request->note,
                'updated_at' => now(),
            ]);

        return response()->json(['success' => (bool) $updated]);
    }

    public function n8nLogs(Request $request)
    {
        $query = DB::table('admission_n8n_logs')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('workflow', 'like', '%' . $keyword . '%')
                    ->orWhere('event_type', 'like', '%' . $keyword . '%')
                    ->orWhere('message', 'like', '%' . $keyword . '%');
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(30),
        ]);
    }

    public function documents()
    {
        $chunkCounts = DB::table('admission_rag_chunks')
            ->select('document_id', DB::raw('COUNT(*) as chunks_count'))
            ->groupBy('document_id');

        $documents = DB::table('admission_rag_documents as d')
            ->leftJoinSub($chunkCounts, 'cc', function ($join) {
                $join->on('cc.document_id', '=', 'd.id');
            })
            ->select('d.*', DB::raw('COALESCE(cc.chunks_count, 0) as chunks_count'))
            ->orderByDesc('d.updated_at')
            ->get();

        return response()->json(['success' => true, 'data' => $documents]);
    }

    public function storeDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_id' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'category' => 'required|string|max:80',
            'status' => 'required|in:active,draft,archived',
            'source_url' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'doc_files' => 'nullable|array',
            'doc_files.*' => 'file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $id = (int) ($data['document_id'] ?? 0);
        
        $files = $request->file('doc_files') ?? [];
        
        // Kiểm tra định dạng file thủ công để tránh lỗi nhận dạng MIME của PHP/Laravel
        $allowedExtensions = ['txt', 'docx', 'json', 'pdf'];
        foreach ($files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowedExtensions)) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'doc_files' => ["File '{$file->getClientOriginalName()}' không đúng định dạng. Chỉ chấp nhận các file: " . implode(', ', $allowedExtensions)]
                    ]
                ], 422);
            }
        }

        $totalChunks = 0;
        $savedDocumentsCount = 0;

        if (count($files) > 0) {
            foreach ($files as $index => $file) {
                $fileContent = $this->extractTextFromFile($file);
                if (empty($fileContent)) {
                    continue;
                }

                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $title = $fileName;
                if (!empty($data['title'])) {
                    $title = count($files) > 1 ? $data['title'] . ' - ' . $fileName : $data['title'];
                }

                $dbData = [
                    'title' => $title,
                    'category' => $data['category'],
                    'status' => $data['status'],
                    'source_url' => $data['source_url'] ?? null,
                    'content' => $fileContent,
                    'updated_at' => now(),
                ];

                if ($id > 0 && $index === 0) {
                    DB::table('admission_rag_documents')->where('id', $id)->update($dbData);
                    $docId = $id;
                } else {
                    $dbData['created_at'] = now();
                    $docId = DB::table('admission_rag_documents')->insertGetId($dbData);
                }

                // Tải file trực tiếp lên OpenAI Vector Store
                $openAiFileId = $this->rag->uploadFileToOpenAIVectorStore($file->getRealPath(), $file->getClientOriginalName());
                
                // Cập nhật ID file của OpenAI vào DB
                if ($openAiFileId) {
                    DB::table('admission_rag_documents')->where('id', $docId)->update(['source_url' => $openAiFileId]);
                }
                
                $totalChunks += 1;
                $savedDocumentsCount++;
            }

            if ($savedDocumentsCount === 0) {
                return response()->json([
                    'success' => false,
                    'errors' => ['doc_files' => ['Không thể đọc nội dung từ bất kỳ file nào được tải lên.']]
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã tải lên và lưu {$savedDocumentsCount} tài liệu với tổng cộng {$totalChunks} chunks.",
                'chunks' => $totalChunks
            ]);
        } else {
            $content = $data['content'] ?? '';
            if (empty($content)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['content' => ['Nội dung tài liệu hoặc file tải lên không được để trống.']]
                ], 422);
            }

            if (empty($data['title'])) {
                $data['title'] = 'Tài liệu tuyển sinh ' . now()->format('d/m/Y H:i');
            }

            $dbData = [
                'title' => $data['title'],
                'category' => $data['category'],
                'status' => $data['status'],
                'source_url' => $data['source_url'] ?? null,
                'content' => $content,
                'updated_at' => now(),
            ];

            if ($id > 0) {
                DB::table('admission_rag_documents')->where('id', $id)->update($dbData);
                $docId = $id;
            } else {
                $dbData['created_at'] = now();
                $docId = DB::table('admission_rag_documents')->insertGetId($dbData);
            }

            // Tạo file tạm để tải nội dung text lên OpenAI
            $tmpFile = sys_get_temp_dir() . '/' . \Illuminate\Support\Str::slug($data['title']) . '.txt';
            file_put_contents($tmpFile, $content);
            $openAiFileId = $this->rag->uploadFileToOpenAIVectorStore($tmpFile, $data['title'] . '.txt');
            @unlink($tmpFile);

            if ($openAiFileId) {
                DB::table('admission_rag_documents')->where('id', $docId)->update(['source_url' => $openAiFileId]);
            }

            return response()->json([
                'success' => true,
                'id' => $docId,
                'chunks' => 1
            ]);
        }
    }

    private function extractTextFromFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        switch ($extension) {
            case 'txt':
                return file_get_contents($filePath);
            case 'json':
                $jsonContent = file_get_contents($filePath);
                $data = json_decode($jsonContent, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->formatJsonToText($data);
                }
                return $jsonContent;
            case 'docx':
                return $this->extractTextFromDocx($filePath);
            case 'pdf':
                return $this->extractTextFromPdf($filePath);
            default:
                return '';
        }
    }

    private function formatJsonToText($data)
    {
        if (!is_array($data)) {
            return (string) $data;
        }

        $text = '';
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $item) {
                if (isset($item['question']) || isset($item['answer'])) {
                    $q = $item['question'] ?? '';
                    $a = $item['answer'] ?? '';
                    $text .= "Hỏi: " . $q . "\nĐáp: " . $a . "\n\n";
                } elseif (isset($item['title']) || isset($item['content'])) {
                    $t = $item['title'] ?? '';
                    $c = $item['content'] ?? '';
                    $text .= "Tiêu đề: " . $t . "\nNội dung: " . $c . "\n\n";
                } else {
                    $text .= json_encode($item, JSON_UNESCAPED_UNICODE) . "\n\n";
                }
            }
        } else {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $text .= $key . ":\n" . $this->formatJsonToText($value) . "\n\n";
                } else {
                    $text .= $key . ": " . $value . "\n";
                }
            }
        }
        return trim($text);
    }

    private function extractTextFromDocx($filePath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $xmlContent = $zip->getFromIndex($index);
                $zip->close();
                
                $xmlContent = str_replace(['</w:p>', '</w:r>', '<w:tab/>'], ["\n", " ", " "], $xmlContent);
                return strip_tags($xmlContent);
            }
            $zip->close();
        }
        return '';
    }

    private function extractTextFromPdf($filePath)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[extractTextFromPdf] Error parsing PDF: ' . $e->getMessage());
            return '';
        }
    }

    public function askRag(Request $request)
    {
        $request->validate(['question' => 'required|string|max:2000']);

        return response()->json([
            'success' => true,
            'data' => $this->rag->answer($request->question),
        ]);
    }



    public function getOpenAiConfig()
    {
        $apiKey = config('services.openai.key');
        $vectorStoreId = config(
            'services.openai.vector_store_id'
        );
        $instructionsFile = config(
            'services.openai.instructions_file'
        );

        if (!$apiKey || !$vectorStoreId || !$instructionsFile) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa cấu hình đầy đủ OpenAI.',
            ], 500);
        }

        $instructions = is_file($instructionsFile)
            ? (string) file_get_contents($instructionsFile)
            : '';

        $vectorFilesResponse =
            \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->acceptJson()
            ->get(
                'https://api.openai.com/v1/vector_stores/'
                . $vectorStoreId
                . '/files',
                ['limit' => 100]
            );

        if (!$vectorFilesResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Không thể lấy danh sách tài liệu OpenAI.',
            ], 502);
        }

        $allFilesResponse =
            \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->acceptJson()
            ->get(
                'https://api.openai.com/v1/files',
                ['limit' => 10000]
            );

        if (!$allFilesResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Không thể lấy thông tin tài liệu OpenAI.',
            ], 502);
        }

        $allFiles = collect(
            $allFilesResponse->json('data') ?: []
        )->keyBy('id');

        $files = collect(
            $vectorFilesResponse->json('data') ?: []
        )->map(function ($vectorFile) use ($allFiles) {
            $fileId = isset($vectorFile['file_id'])
                ? $vectorFile['file_id']
                : $vectorFile['id'];

            $fileInfo = $allFiles->get($fileId);

            return [
                'id' => $fileId,
                'filename' => $fileInfo
                    ? $fileInfo['filename']
                    : 'Unknown',
                'bytes' => $fileInfo
                    ? round(
                        $fileInfo['bytes'] / 1024,
                        2
                    ) . ' KB'
                    : '0 KB',
                'created_at' => $fileInfo
                    ? date(
                        'Y-m-d H:i:s',
                        $fileInfo['created_at']
                    )
                    : '',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'instructions' => $instructions,
                'files' => $files,
            ],
        ]);
    }

    public function updateOpenAiPrompt(Request $request)
    {
        $data = $request->validate([
            'instructions' => 'required|string',
        ]);

        $instructionsFile = config(
            'services.openai.instructions_file'
        );

        if (!$instructionsFile) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Chưa cấu hình file prompt OpenAI.',
            ], 500);
        }

        $directory = dirname($instructionsFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $written = file_put_contents(
            $instructionsFile,
            $data['instructions'],
            LOCK_EX
        );

        return response()->json([
            'success' => $written !== false,
        ], $written !== false ? 200 : 500);
    }

    public function deleteOpenAiFile($fileId)
    {
        $apiKey = config('services.openai.key');

        $response =
            \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->acceptJson()
            ->delete(
                'https://api.openai.com/v1/files/'
                . $fileId
            );

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Không thể xóa tài liệu trên OpenAI.',
            ], 502);
        }

        DB::table('admission_rag_documents')
            ->where('source_url', $fileId)
            ->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function uploadOpenAiFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');

        $fileId = $this->rag->uploadFileToOpenAIVectorStore(
            $file->getRealPath(),
            $file->getClientOriginalName()
        );

        if (!$fileId) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Không thể tải tài liệu lên OpenAI.',
            ], 500);
        }

        DB::table('admission_rag_documents')->insert([
            'title' => $file->getClientOriginalName(),
            'category' => 'openai_direct',
            'status' => 'active',
            'source_url' => $fileId,
            'content' => 'Uploaded to OpenAI Vector Store',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}