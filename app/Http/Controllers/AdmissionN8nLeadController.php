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
        $rules = [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'high_school' => 'nullable|string|max:255',
        ];
        
        // Validation động cho custom fields
        $criteria = \App\Models\AdmissionScoringCriterion::where('is_active', true)
            ->where('show_on_public_form', true)
            ->get();
            
        foreach ($criteria as $criterion) {
            if ($criterion->is_required) {
                $customKey = str_replace('custom.', '', $criterion->data_field);
                $rules["custom.{$customKey}"] = 'required';
            }
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng điền đầy đủ các thông tin bắt buộc.',
                'errors' => $validator->errors()
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
                'source' => $request->input('utm_source'),
                'medium' => $request->input('utm_medium'),
                'campaign' => $request->input('utm_campaign'),
                'content' => $request->input('utm_content'),
            ],
            'high_school' => $request->input('high_school'),
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

        // Bắn dữ liệu sang n8n Master Webhook thông qua Hàng đợi (Queue)
        try {
            $payload = [
                'event_type' => 'new_lead',
                'data' => [
                    'id' => $leadId,
                    'name' => $leadData['full_name'],
                    'email' => $leadData['email'] ?? '',
                    'phone' => $leadData['phone'] ?? '',
                    'question' => $request->input('note', ''),
                    'province' => $request->input('province', ''),
                    'intended_major' => $request->input('intended_major', ''),
                    'high_school' => $request->input('high_school', ''),
                    'score' => $scored['lead_score'] ?? 0,
                    'score_grade' => strtolower($scored['lead_level'] ?? 'cold'),
                    'custom_fields' => $mergedCustom
                ]
            ];
            
            \App\Jobs\SendLeadToN8n::dispatch($payload);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Loi dispatch job n8n: ' . $e->getMessage());
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
