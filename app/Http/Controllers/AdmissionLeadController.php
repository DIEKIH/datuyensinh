<?php

namespace App\Http\Controllers;

use App\Services\AdmissionLeadScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class AdmissionLeadController extends Controller
{
    private $scoring;
    private $rag;

    public function __construct(AdmissionLeadScoringService $scoring, \App\Services\AdmissionRagService $rag)
    {
        $this->scoring = $scoring;
        $this->rag = $rag;
    }

    public function create(Request $request)
    {
        return view('users.pages.dang_ky_tu_van', [
            'utm' => [
                'source' => $request->query('utm_source', 'website'),
                'medium' => $request->query('utm_medium'),
                'campaign' => $request->query('utm_campaign'),
                'content' => $request->query('utm_content'),
            ],
            'major' => $request->query('major'),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'intended_major' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:5000',
            'utm_source' => 'nullable|string|max:80',
            'utm_medium' => 'nullable|string|max:80',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
            'high_school' => 'nullable|string|max:255',
        ], [
            'full_name.required' => 'Vui long nhap ho ten.',
            'phone.required' => 'Vui long nhap so dien thoai.',
            'email.email' => 'Email khong hop le.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $channel = $this->normalizeChannel($data['utm_source'] ?? 'website');
        $lead = DB::table('admission_leads')->where('phone', $data['phone'])->first();

        $leadData = [
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? optional($lead)->email,
            'channel' => $channel,
            'source_campaign' => $data['utm_campaign'] ?? optional($lead)->source_campaign,
            'intended_major' => $data['intended_major'] ?? optional($lead)->intended_major,
            'province' => $data['province'] ?? optional($lead)->province,
            'status' => optional($lead)->status ?? 'new',
            'note' => $data['note'] ?? optional($lead)->note,
            'last_interaction_at' => now(),
        ];

        $customInput = $request->input('custom', []);
        $mergedCustom = $this->scoring->extractAndMergeCustomFields($customInput);

        $oldProfileArr = [];
        if ($lead && !empty($lead->profile)) {
            $oldProfileArr = json_decode($lead->profile, true) ?: [];
            if (isset($oldProfileArr['custom']) && is_array($oldProfileArr['custom'])) {
                $mergedCustom = array_merge($oldProfileArr['custom'], $mergedCustom);
            }
        }
        $leadData['custom'] = $mergedCustom;

        $scored = $this->scoring->score($leadData, [['type' => 'form_submit']]);
        $leadData['score'] = $scored['lead_score'] ?? 0;
        $leadData['score_grade'] = strtolower($scored['lead_level'] ?? 'cold');
        $leadData['profile'] = json_encode(array_merge($oldProfileArr, [
            'custom' => $mergedCustom,
            'score_reasons' => $scored['matched_criteria'] ?? [],
            'utm' => [
                'source' => $data['utm_source'] ?? null,
                'medium' => $data['utm_medium'] ?? null,
                'campaign' => $data['utm_campaign'] ?? null,
                'content' => $data['utm_content'] ?? null,
            ],
            'high_school' => $data['high_school'] ?? null,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]));
        $leadData['updated_at'] = now();

        $dbData = $leadData;
        unset($dbData['custom']);

        if ($lead) {
            DB::table('admission_leads')->where('id', $lead->id)->update($dbData);
            $leadId = $lead->id;
        } else {
            $dbData['created_at'] = now();
            $leadId = DB::table('admission_leads')->insertGetId($dbData);
        }

        // Lưu tài khoản tương tác kênh Form/Website (dùng SĐT làm account_id)
        DB::table('admission_lead_channel_accounts')->updateOrInsert(
            ['channel' => $channel, 'account_id' => $data['phone']],
            ['lead_id' => $leadId, 'last_interaction_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );

        DB::table('admission_lead_activities')->insert([
            'lead_id' => $leadId,
            'channel' => $channel,
            'type' => 'form_submit',
            'external_id' => null,
            'direction' => 'inbound',
            'content' => $data['note'] ?? 'Dang ky tu van tu landing page',
            'payload' => json_encode($request->all()),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // [MỚI THÊM] Bắn dữ liệu sang n8n Master Webhook để AI xử lý
        if (!empty($data['note'])) {
            try {
                // Thay thế URL này bằng URL Webhook thực tế của bạn trong n8n (Production/Test)
                $n8nUrl = env('N8N_MASTER_WEBHOOK_URL', 'http://localhost:5678/webhook/master');
                Http::post($n8nUrl, [
                    'event_type' => 'new_lead',
                    'data' => [
                        'id' => $leadId,
                        'name' => $leadData['full_name'],
                        'email' => $leadData['email'],
                        'phone' => $leadData['phone'],
                        'question' => $data['note']
                    ]
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Loi gui webhook n8n: ' . $e->getMessage());
            }
        }

        // Tự động tìm câu trả lời RAG ngay nếu thí sinh có câu hỏi ở phần ghi chú
        $ragAnswer = null;
        if (!empty($data['note'])) {
            $askQuery = $data['note'];
            if (!empty($data['intended_major'])) {
                $askQuery .= "\n[Ghi chú hệ thống: Thí sinh này đang chọn ngành quan tâm là " . $data['intended_major'] . " trên form. Nếu thí sinh hỏi điểm chuẩn mà không nói rõ ngành, hãy chỉ báo điểm chuẩn của ngành " . $data['intended_major'] . " này thôi, tuyệt đối KHÔNG liệt kê các ngành khác.]";
            }
            $ragResult = $this->rag->answer($askQuery);
            $ragAnswer = $ragResult['answer'] ?? null;
            
            $isEasyQuestion = false;
            // Nếu AI sinh ra câu trả lời và không phải là lời từ chối
            if ($ragAnswer && strpos($ragAnswer, 'Chưa tìm thấy thông tin phù hợp') === false) {
                $isEasyQuestion = true;
            }

            // Tạo Session ảo để có thể lưu Ticket sang mục Tư Vấn
            $sessionId = DB::table('chatbot_sessions')->insertGetId([
                'session_key' => 'form_' . \Illuminate\Support\Str::random(10),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'started_at' => now(),
                'last_active_at' => now(),
            ]);

            // Tạo Ticket trong bảng chatbot_tickets (Hiển thị ở màn hình Tư vấn)
            $ticketCode = 'TV' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
            DB::table('chatbot_tickets')->insert([
                'session_id' => $sessionId,
                'ticket_code' => $ticketCode,
                'question' => "Câu hỏi từ Form ({$data['full_name']} - {$data['phone']}):\n" . $data['note'],
                'status' => $isEasyQuestion ? 'answered' : 'pending',
                'staff_answer' => $isEasyQuestion ? $ragAnswer : null,
                'answered_by' => null,
                'answered_at' => $isEasyQuestion ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Nếu câu hỏi dễ giải quyết bằng AI và thí sinh có để lại email -> Tự động gửi Email
            if ($isEasyQuestion && !empty($data['email'])) {
                try {
                    \Illuminate\Support\Facades\Mail::raw(
                        "Chào bạn {$data['full_name']},\n\nCảm ơn bạn đã gửi thắc mắc đến Trường Đại học Kỹ thuật - Công nghệ Cần Thơ (CTUT).\n\nCâu hỏi của bạn:\n{$data['note']}\n\nTrả lời:\n{$ragAnswer}\n\nNếu bạn quan tâm thì đến web trường tìm hiểu, có thắc mắc thì hỏi chat bot.\n\nTrân trọng,\nTrợ lý tuyển sinh CTUT",
                        function ($message) use ($data) {
                            $message->to($data['email'])
                                    ->subject('Giải đáp thông tin tuyển sinh CTUT');
                        }
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Lỗi gửi email tự động từ Form: ' . $e->getMessage());
                }
            }
        }



        return response()->json([
            'success' => true,
            'message' => 'Da ghi nhan thong tin. Bo phan tu van se lien he trong thoi gian som nhat.',
            'lead_id' => $leadId,
            'score' => $scored['lead_score'] ?? 0,
            'score_grade' => strtolower($scored['lead_level'] ?? 'cold'),
        ]);
    }

    private function normalizeChannel($source)
    {
        $source = strtolower(trim((string) $source));

        if (strpos($source, 'facebook') !== false || $source === 'fb') {
            return 'facebook';
        }

        if (strpos($source, 'zalo') !== false) {
            return 'zalo';
        }

        if (strpos($source, 'tiktok') !== false) {
            return 'tiktok';
        }

        return $source ?: 'website';
    }
}
