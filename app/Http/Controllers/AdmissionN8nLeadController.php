<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\AdmissionLeadScoringService;

class AdmissionN8nLeadController extends Controller
{
    protected $scoring;

    public function __construct(AdmissionLeadScoringService $scoring)
    {
        $this->scoring = $scoring;
    }

    public function create()
    {
        return view('pages.dang_ky_tu_van');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
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
        $channel = $this->normalizeChannel($request->input('utm_source', 'website'));
        $lead = DB::table('admission_leads')->where('phone', $data['phone'])->first();

        $leadData = [
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? optional($lead)->email,
            'channel' => $channel,
            'source_campaign' => $request->input('utm_campaign', optional($lead)->source_campaign),
            'intended_major' => $request->input('intended_major', optional($lead)->intended_major),
            'province' => $request->input('province', optional($lead)->province),
            'status' => optional($lead)->status ?? 'new',
            'note' => $request->input('note', optional($lead)->note),
            'last_interaction_at' => now(),
        ];

        $scored = $this->scoring->score($leadData, [['type' => 'form_submit']]);
        $leadData['score'] = $scored['lead_score'] ?? 0;
        $leadData['score_grade'] = strtolower($scored['lead_level'] ?? 'cold');
        $leadData['profile'] = json_encode([
            'score_reasons' => $scored['matched_criteria'] ?? [],
            'utm' => [
                'source' => $request->input('utm_source'),
                'medium' => $request->input('utm_medium'),
                'campaign' => $request->input('utm_campaign'),
                'content' => $request->input('utm_content'),
            ],
            'high_school' => $request->input('high_school'),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);
        $leadData['updated_at'] = now();

        if ($lead) {
            DB::table('admission_leads')->where('id', $lead->id)->update($leadData);
            $leadId = $lead->id;
        } else {
            $leadData['created_at'] = now();
            $leadId = DB::table('admission_leads')->insertGetId($leadData);
        }

        DB::table('admission_lead_activities')->insert([
            'lead_id' => $leadId,
            'channel' => $channel,
            'type' => 'form_submit',
            'external_id' => null,
            'direction' => 'inbound',
            'content' => $request->input('note', 'Dang ky tu van tu landing page'),
            'payload' => json_encode($request->all()),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Bắn dữ liệu sang n8n Master Webhook để n8n điều phối toàn bộ (Sử dụng AI Assistant)
        try {
            $n8nUrl = env('N8N_MASTER_WEBHOOK_URL', 'http://localhost:5678/webhook/master-receiver');
            \Illuminate\Support\Facades\Http::withoutVerifying()->post($n8nUrl, [
                'event_type' => 'new_lead',
                'data' => [
                    'id' => $leadId,
                    'name' => $leadData['full_name'],
                    'email' => $leadData['email'] ?? '',
                    'phone' => $leadData['phone'] ?? '',
                    'question' => $request->input('note', ''),
                ]
            ])->throw();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Loi gui webhook n8n: ' . $e->getMessage());
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
