<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdmissionRagService
{
    private $apiKey;
    private $assistantId;
    private $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        // Thêm OPENAI_ASSISTANT_ID vào file .env nhé!
        $this->assistantId = env('OPENAI_ASSISTANT_ID'); 
    }

    public function extractLeadInfo($text)
    {
        if (!$this->apiKey || empty($text)) return [];
        
        try {
            $response = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Trích xuất thông tin khách hàng từ tin nhắn sau. Trả về JSON với các key: "phone" (số điện thoại nếu có, null nếu không), "email" (email nếu có, null nếu không), "major" (ngành học khách quan tâm, null nếu không rõ), "intent" (có thể là: "ask_major", "ask_tuition", "register", "other"). Trả về ĐÚNG ĐỊNH DẠNG JSON, không bọc trong markdown.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $text
                        ]
                    ],
                    'response_format' => ['type' => 'json_object']
                ]);
                
            $content = $response->json('choices.0.message.content');
            if ($content) {
                return json_decode($content, true) ?? [];
            }
        } catch (\Throwable $e) {
            Log::error('Extract AI failed: ' . $e->getMessage());
        }
        
        return [];
    }

    public function answer($question, $sessionId = null, $mediaUrl = null)
    {
        if (!$this->apiKey || !$this->assistantId) {
            return [
                'answer' => 'Hệ thống chưa được cấu hình OpenAI Assistant (Thiếu API Key hoặc Assistant ID).',
                'sources' => [],
                'generated' => false,
            ];
        }

        // 1. Kiểm tra Cache / Bảng câu hỏi cũ
        $similarAnswer = $this->findSimilarStaffAnswer($question);
        
        // Nếu tìm thấy câu hỏi cũ (khớp cao), trả về thẳng luôn để tốn 0 token!
        if ($similarAnswer && $similarAnswer['score'] > 5.0) {
            return [
                'answer' => $similarAnswer['answer'],
                'sources' => ['Câu trả lời chuẩn từ Tư vấn viên'],
                'generated' => false,
            ];
        }

        // Chuẩn bị tin nhắn gửi cho Assistant
        $messageContent = "Câu hỏi của thí sinh: " . $question;

        // Nếu có câu cũ nhưng độ khớp chưa đủ an toàn tuyệt đối, bơm vào làm ngữ cảnh tham khảo
        if ($similarAnswer) {
            $messageContent .= "\n\n[GHI CHÚ HỆ THỐNG]: Trước đây nhân viên nhà trường đã trả lời một câu hỏi tương tự như sau:\n" 
                             . strip_tags($similarAnswer['answer']) 
                             . "\n(Hãy tham khảo thông tin này để trả lời thí sinh, nhưng hãy diễn đạt lại bằng văn phong tự nhiên của bạn).";
        }

        // 2. Lấy hoặc tạo Thread ID (Để AI có trí nhớ liên tục)
        $threadId = $this->getOrCreateThread($sessionId);

        // 3. Đẩy tin nhắn vào Thread (có hỗ trợ Vision AI nếu có mediaUrl)
        $this->addMessageToThread($threadId, $messageContent, $mediaUrl);

        // 4. Kích hoạt Assistant xử lý (Run)
        $run = $this->runAssistant($threadId);

        // 5. Polling đợi Assistant xử lý xong và lấy câu trả lời
        $answerContent = $this->pollForCompletion($threadId, $run['id']);

        return [
            'answer' => $answerContent,
            'sources' => [],
            'generated' => true,
        ];
    }

    private function getOrCreateThread($sessionId)
    {
        $cacheKey = 'assistant_thread_' . $sessionId;
        
        if ($sessionId && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Tạo Thread mới qua API
        $response = Http::withToken($this->apiKey)
            ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
            ->post("{$this->baseUrl}/threads");

        $threadId = $response->json('id');

        if ($sessionId && $threadId) {
            Cache::put($cacheKey, $threadId, now()->addHours(24)); // Lưu trí nhớ trong 24h
        }

        return $threadId;
    }

    private function addMessageToThread($threadId, $content, $mediaUrl = null)
    {
        $messagePayload = [];
        
        if ($mediaUrl) {
            // Dùng Vision: Đưa content thành mảng text + image_url
            $messagePayload = [
                [
                    'type' => 'text',
                    'text' => $content
                ],
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $mediaUrl
                    ]
                ]
            ];
        } else {
            // Chat bình thường
            $messagePayload = $content;
        }

        Http::withToken($this->apiKey)
            ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
            ->post("{$this->baseUrl}/threads/{$threadId}/messages", [
                'role' => 'user',
                'content' => $messagePayload
            ]);
    }

    private function runAssistant($threadId)
    {
        $additionalInstruction = "Nếu bạn không tìm thấy thông tin trong tài liệu hoặc người dùng đang cáu gắt, TUYỆT ĐỐI không bịa ra câu trả lời. Hãy trả lời chính xác câu này: 'Câu hỏi này nằm ngoài thông tin hiện có của mình. Bạn vui lòng để lại Tên và Số điện thoại (hoặc Email), các Thầy/Cô ban tư vấn sẽ liên hệ trực tiếp để hỗ trợ bạn ngay nhé!'";

        $response = Http::withToken($this->apiKey)
            ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
            ->post("{$this->baseUrl}/threads/{$threadId}/runs", [
                'assistant_id' => $this->assistantId,
                'additional_instructions' => $additionalInstruction
            ]);
            
        return $response->json();
    }

    private function pollForCompletion($threadId, $runId)
    {
        $maxRetries = 40; // Đợi tối đa 40s
        $attempts = 0;

        while ($attempts < $maxRetries) {
            $response = Http::withToken($this->apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->get("{$this->baseUrl}/threads/{$threadId}/runs/{$runId}");

            $status = $response->json('status');

            if ($status === 'completed') {
                // Lấy danh sách messages
                $messagesResponse = Http::withToken($this->apiKey)
                    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                    ->get("{$this->baseUrl}/threads/{$threadId}/messages");
                    
                $messages = $messagesResponse->json('data');
                
                // Lấy tin nhắn mới nhất do assistant phản hồi
                foreach ($messages as $msg) {
                    if ($msg['role'] === 'assistant') {
                        return $msg['content'][0]['text']['value'] ?? 'Có lỗi khi đọc nội dung.';
                    }
                }
            }

            // Nếu bị lỗi hoặc hết hạn
            if (in_array($status, ['failed', 'cancelled', 'expired'])) {
                Log::error("OpenAI Assistant Run failed: " . json_encode($response->json()));
                return "Xin lỗi, hệ thống AI đang quá tải hoặc gặp sự cố. Vui lòng thử lại sau hoặc liên hệ Hotline.";
            }

            sleep(1); // Ngủ 1 giây rồi check lại
            $attempts++;
        }

        return "Xin lỗi, hệ thống AI phản hồi quá lâu. Vui lòng gửi lại câu hỏi hoặc liên hệ Hotline.";
    }

    private function findSimilarStaffAnswer($question)
    {
        $question = trim($question);
        if (empty($question)) {
            return null;
        }

        try {
            // Tìm bằng Full-Text Search những câu đã được trả lời, lấy kèm độ chính xác (score)
            $ticket = DB::table('chatbot_tickets')
                ->select('*', DB::raw('MATCH(question, staff_answer) AGAINST(? IN NATURAL LANGUAGE MODE) AS score'))
                ->addBinding($question, 'select')
                ->where('status', 'answered')
                ->where('is_public', 1)
                ->whereNotNull('staff_answer')
                ->where(function ($q) use ($question) {
                    $q->whereRaw('MATCH(question, staff_answer) AGAINST(? IN NATURAL LANGUAGE MODE)', [$question]);
                })
                ->orderByDesc('score')
                ->first();

            if ($ticket) {
                return [
                    'question' => $ticket->question,
                    'answer' => $ticket->staff_answer,
                    'score' => $ticket->score ?? 0
                ];
            }
        } catch (\Throwable $e) {
            // Bỏ qua nếu lỗi SQL
        }

        return null;
    }

    public function rebuildChunks($documentId, $content, $chunkSize = 1000, $chunkOverlap = 200)
    {
        return 1;
    }

    public function uploadFileToOpenAIVectorStore($filePath, $fileName = null)
    {
        if (!$this->apiKey || !$this->assistantId) return false;

        // 1. Upload File lên bộ nhớ của OpenAI
        $name = $fileName ? $fileName : basename($filePath);
        $response = Http::withToken($this->apiKey)
            ->attach('file', file_get_contents($filePath), $name)
            ->post("{$this->baseUrl}/files", [
                'purpose' => 'assistants'
            ]);

        $fileId = $response->json('id');
        if (!$fileId) return false;

        // 2. Lấy Vector Store ID hiện tại của con Assistant
        $asstResponse = Http::withToken($this->apiKey)
            ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
            ->get("{$this->baseUrl}/assistants/{$this->assistantId}");
            
        $vectorStoreId = $asstResponse->json('tool_resources.file_search.vector_store_ids.0');

        // 3. Đưa file vừa upload vào Vector Store để Assistant đọc được
        if ($vectorStoreId) {
            Http::withToken($this->apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->post("{$this->baseUrl}/vector_stores/{$vectorStoreId}/files", [
                    'file_id' => $fileId
                ]);
        }

        return $fileId;
    }
}
