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
            $channel = $request->input('channel', 'facebook');
            $content = $request->input('question', $request->input('content', ''));
            $mediaUrl = $request->input('media_url');
            $senderId = $request->input('sender_id');
            $isComment = $request->input('is_comment', false);
            
            // 1. Find or create lead
            $lead = DB::table('admission_leads')->where('profile->sender_id', $senderId)->first();
            $leadId = null;
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
            } elseif ($lead) {
                $leadId = $lead->id;
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
            
            // 4. Call RAG
            $ragResult = null;
            if (!empty($content) || !empty($mediaUrl)) {
                $ragResult = $this->rag->answer($content, $senderId, $mediaUrl);
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
        // Route to upsertLead so scoring works
        return $this->upsertLead($request);
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
