<?php

namespace App\Http\Controllers;

use App\Models\SocialPostApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SocialPostApprovalController extends Controller
{
    public function page()
    {
        return view('admins.pages.admission.social_post_approvals');
    }

    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        if (!in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $query = SocialPostApproval::query()
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(12),
        ]);
    }

    public function storeFromN8n(Request $request)
    {
        $data = $request->all();

        if (array_key_exists('article_id', $data) && $data['article_id'] !== null) {
            $data['article_id'] = (string) $data['article_id'];
        }

        if (array_key_exists('workflow_id', $data) && $data['workflow_id'] !== null) {
            $data['workflow_id'] = (string) $data['workflow_id'];
        }

        if (array_key_exists('execution_id', $data) && $data['execution_id'] !== null) {
            $data['execution_id'] = (string) $data['execution_id'];
        }

        $validator = Validator::make($data, [
            'approval_key' => 'required|string|max:191',
            'workflow_id' => 'nullable|string|max:100',
            'execution_id' => 'nullable|string|max:100',
            'article_id' => 'nullable|string|max:100',
            'title' => 'required|string|max:500',
            'article_url' => 'nullable|url|max:2048',
            'caption' => 'nullable|string|max:30000',
            'hashtags' => 'nullable|string|max:3000',
            'content_images' => 'nullable|array|max:10',
            'content_images.*' => 'nullable|url|max:2048',
            'platforms' => 'nullable|array|max:10',
            'platforms.*' => 'string|max:50',
            'ai_review' => 'nullable|array',
            'resume_url' => 'required|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu bài viết chờ duyệt không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (!$this->resumeUrlIsAllowed($validated['resume_url'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tên miền resume URL của n8n chưa được cho phép.',
            ], 422);
        }

        $approval = SocialPostApproval::where('approval_key', $validated['approval_key'])
            ->first();

        if ($approval && $approval->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Bài viết này đã được xử lý trước đó.',
                'data' => $approval,
            ], 409);
        }

        $approval = SocialPostApproval::updateOrCreate(
            ['approval_key' => $validated['approval_key']],
            array_merge($validated, [
                'status' => 'pending',
                'decision_note' => null,
                'decided_by' => null,
                'decided_at' => null,
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã đưa bài viết lên màn hình chờ duyệt.',
            'data' => [
                'id' => $approval->id,
                'status' => $approval->status,
            ],
        ], $approval->wasRecentlyCreated ? 201 : 200);
    }

    public function action(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
            'caption' => 'nullable|string|max:30000',
            'hashtags' => 'nullable|string|max:3000',
            'reason' => 'nullable|string|max:3000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu xử lý không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($data['action'] === 'approve' && trim((string) ($data['caption'] ?? '')) === '') {
            return response()->json([
                'success' => false,
                'message' => 'Nội dung bài đăng không được để trống.',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($id, $data) {
                $approval = SocialPostApproval::whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (!$approval) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy bài viết chờ duyệt.',
                    ], 404);
                }

                if ($approval->status !== 'pending') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bài viết này đã được xử lý.',
                    ], 409);
                }

                if (!$this->resumeUrlIsAllowed($approval->resume_url)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Resume URL của n8n không còn hợp lệ.',
                    ], 422);
                }

                $callbackPayload = [
                    'action' => $data['action'],
                    'approval_id' => $approval->id,
                    'caption' => $data['action'] === 'approve'
                        ? trim((string) $data['caption'])
                        : (string) $approval->caption,
                    'hashtags' => $data['action'] === 'approve'
                        ? trim((string) ($data['hashtags'] ?? ''))
                        : (string) $approval->hashtags,
                    'reason' => trim((string) ($data['reason'] ?? '')),
                ];

                $response = Http::timeout(20)
                    ->acceptJson()
                    ->post($approval->resume_url, $callbackPayload);

                if (!$response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể tiếp tục execution đang chờ của n8n.',
                        'n8n_status' => $response->status(),
                    ], 502);
                }

                $approval->caption = $callbackPayload['caption'];
                $approval->hashtags = $callbackPayload['hashtags'];
                $approval->status = $data['action'] === 'approve'
                    ? 'approved'
                    : 'rejected';
                $approval->decision_note = $callbackPayload['reason'] ?: null;
                $approval->decided_by = auth()->id();
                $approval->decided_at = now();
                $approval->save();

                return response()->json([
                    'success' => true,
                    'message' => $data['action'] === 'approve'
                        ? 'Đã duyệt và chuyển bài viết sang bước đăng.'
                        : 'Đã từ chối bài viết.',
                    'data' => $approval,
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Không kết nối được với execution đang chờ của n8n.',
            ], 502);
        }
    }

    private function resumeUrlIsAllowed($url)
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!$host || !in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $configured = env(
            'N8N_RESUME_ALLOWED_HOSTS',
            'localhost,127.0.0.1,n8n'
        );

        $hosts = array_filter(array_map(function ($value) {
            return strtolower(trim($value));
        }, explode(',', $configured)));

        foreach (['N8N_BASE_URL', 'N8N_EDITOR_BASE_URL', 'N8N_WEBHOOK_URL'] as $key) {
            $baseHost = strtolower((string) parse_url((string) env($key), PHP_URL_HOST));
            if ($baseHost) {
                $hosts[] = $baseHost;
            }
        }

        return in_array($host, array_unique($hosts), true);
    }
}
