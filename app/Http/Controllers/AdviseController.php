<?php

namespace App\Http\Controllers;

use App\Jobs\SaveAdviseMessage;
use App\Models\AdviseMessage;
use App\Models\AdviseSession;
use App\Models\AdviseTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdviseController extends Controller
{
    private const OAI_BASE = 'https://api.openai.com/v1';

    private function oaiHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . config('services.openai.key'),
            'OpenAI-Beta'   => 'assistants=v2',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 1. Tạo thread OpenAI
    // ─────────────────────────────────────────────────────────────
    public function createThread()
    {
        try {
            $res = Http::withHeaders($this->oaiHeaders())
                ->post(self::OAI_BASE . '/threads');

            $json = $res->json();

            if ($res->successful() && isset($json['id'])) {
                session(['advise_thread_id' => $json['id']]);
            }

            return response()->json($json, $res->status());
        } catch (\Throwable $e) {
            Log::error('[createThread] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo phiên chat.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

   // ─────────────────────────────────────────────────────────────
// 2. Lưu user message trước, sau đó mới gửi sang OpenAI thread
// ─────────────────────────────────────────────────────────────
public function addMessage(Request $request)
{
    $userMessage = null;
    try {
        $data = $request->validate([
            'thread_id' => 'required|string|max:128',
            'content'   => 'required|string|max:12000',
        ]);

        $threadId = trim($data['thread_id']);
        $content  = trim($data['content']);

        if ($content === '') {
            return response()->json([
                'success' => false,
                'message' => 'Nội dung tin nhắn không được để trống.',
            ], 422);
        }

        session(['advise_thread_id' => $threadId]);

        // 1. Tạo/cập nhật phiên chat nội bộ
        $session = $this->getOrCreateSession($request, $threadId);

        // ĐỒNG BỘ LEAD: Quét thông tin và cập nhật hoặc tạo Lead
        $leadId = $this->extractAndSyncLead($request, $content, $threadId);
        if ($leadId) {
            session(['advise_lead_id' => $leadId]);
            $this->logLeadActivity($leadId, 'user', $content);
        }

        // 2. Lưu câu hỏi của người dùng vào CSDL trước
        $userMessage = AdviseMessage::create([
            'session_id'  => $session->id,
            'reply_to_id' => null,
            'role'        => 'user',
            'content'     => $content,
            'input_type'  => 'text',
            'sent_at'     => now(),
        ]);

        // 3. Chỉ khi lưu DB thành công mới gửi sang OpenAI
        $res = Http::withHeaders($this->oaiHeaders())
            ->post(self::OAI_BASE . "/threads/{$threadId}/messages", [
                'role'    => 'user',
                'content' => $content,
            ]);

        if (!$res->successful()) {
            // Xóa tin nhắn local để tránh lệch pha dữ liệu lịch sử chat
            if ($userMessage) {
                $userMessage->delete();
            }

            Log::error('[addMessage] OpenAI error after user message saved. Local message deleted.', [
                'session_id'      => $session->id,
                'user_message_id' => optional($userMessage)->id,
                'thread_id'       => $threadId,
                'status'          => $res->status(),
                'body'            => $res->body(),
            ]);

            return response()->json([
                'success'         => false,
                'message'         => 'Chưa thể gửi tin nhắn đến hệ thống AI tuyển sinh. Vui lòng thử lại.',
                'thread_id'       => $threadId,
                'openai_status'   => $res->status(),
            ], 502);
        }

        return response()->json([
            'success'         => true,
            'message'         => 'Đã lưu câu hỏi và gửi đến hệ thống AI.',
            'thread_id'       => $threadId,
            'session_id'      => $session->id,
            'user_message_id' => $userMessage->id,
            'openai'          => $res->json(),
        ]);
    } catch (\Throwable $e) {
        // Nếu đã tạo tin nhắn local nhưng gặp lỗi trong quá trình gọi OpenAI (ví dụ: Timeout, No Internet...)
        if ($userMessage) {
            try {
                $userMessage->delete();
            } catch (\Throwable $deleteEx) {
                Log::error('[addMessage] Failed to delete orphan local message: ' . $deleteEx->getMessage());
            }
        }

        Log::error('[addMessage] Exception occurred: ' . $e->getMessage(), [
            'exception' => $e
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Không thể gửi tin nhắn đến hệ thống AI. Vui lòng kiểm tra lại kết nối.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    // ─────────────────────────────────────────────────────────────
    // 3. Stream run
    // ─────────────────────────────────────────────────────────────
    public function stream(Request $request)
    {
        $request->validate([
            'thread_id'       => 'required|string|max:128',
            'user_message_id' => 'nullable|integer',
        ]);

        $tid = $request->thread_id;

        session(['advise_thread_id' => $tid]);

        // AI Tự Học: Tìm kiếm câu trả lời tương tự từ tư vấn viên trong database
        $userMessage = null;
        if ($request->user_message_id) {
            $userMessage = AdviseMessage::find($request->user_message_id);
        }

        $additionalInstructions = "Hãy trả lời một cách lịch sự, ngắn gọn và chính xác dựa trên tài liệu tuyển sinh.\n[QUAN TRỌNG - BẢO VỆ THÔNG TIN CÁ NHÂN]: Tuyệt đối KHÔNG hiển thị hoặc lặp lại các thông tin cá nhân nhạy cảm của thí sinh (như Số điện thoại cụ thể, Email, Số CCCD/CMND, Địa chỉ nhà riêng, Điểm thi chi tiết kèm họ tên thật...) trong câu trả lời. Nếu cần nhắc đến thông tin liên hệ, hãy hướng dẫn thí sinh liên hệ qua kênh hotline chính thức của trường.";

        if ($userMessage) {
            $similarAnswer = $this->findSimilarStaffAnswer($userMessage->content);
            if ($similarAnswer) {
                $additionalInstructions .= "\n\n[QUAN TRỌNG - THAM KHẢO THỰC TẾ]: Trước đây, nhân viên tư vấn đã trả lời một câu hỏi tương tự như sau:\n";
                $cleanQuestion = $this->anonymizeText($similarAnswer['question']);
                $cleanAnswer = $this->anonymizeText(strip_tags($similarAnswer['answer']));
                $additionalInstructions .= "Câu hỏi của thí sinh: \"" . $cleanQuestion . "\"\n";
                $additionalInstructions .= "Câu trả lời của tư vấn viên: \"" . $cleanAnswer . "\"\n";
                $additionalInstructions .= "Hãy ƯU TIÊN tham khảo câu trả lời thực tế trên để trả lời người dùng, nhưng hãy trình bày một cách tự nhiên và phù hợp với ngữ cảnh hội thoại hiện tại.";
            }
        }

        $response = Http::withHeaders(array_merge($this->oaiHeaders(), [
            'Accept' => 'text/event-stream',
        ]))
            ->withOptions([
                'stream'  => true,
                'timeout' => 120,
            ])
            ->post(self::OAI_BASE . "/threads/{$tid}/runs", [
                'assistant_id' => config('services.openai.assistant_id'),
                'stream'       => true,
                'additional_instructions' => $additionalInstructions,
                'tools'        => [[
                    'type'        => 'file_search',
                    'file_search' => [
                        'max_num_results' => 5,
                    ],
                ]],
            ]);

        return response()->stream(function () use ($response) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', false);

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            $body = $response->toPsrResponse()->getBody();

            while (!$body->eof()) {
                $chunk = $body->read(1024);

                if ($chunk !== '') {
                    echo $chunk;
                    @ob_flush();
                    flush();
                }
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Reset thread
    // ─────────────────────────────────────────────────────────────
    public function resetThread()
    {
        $threadId = session('advise_thread_id');

        if ($threadId) {
            try {
                Http::withHeaders($this->oaiHeaders())
                    ->delete(self::OAI_BASE . "/threads/{$threadId}");
            } catch (\Throwable $e) {
                Log::warning('[resetThread] Không xoá được thread: ' . $e->getMessage());
            }
        }

        session()->forget('advise_thread_id');

        return response()->json([
            'success' => true,
            'message' => 'Đã làm mới cuộc hội thoại.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 5. Lưu 1 tin nhắn bằng Job
    // ─────────────────────────────────────────────────────────────
    public function saveMessage(Request $request)
    {
        try {
            $data = $request->validate([
                'thread_id'   => 'nullable|string|max:128',
                'role'        => 'required|in:user,assistant',
                'content'     => 'required|string',
                'input_type'  => 'nullable|in:text',
                'reply_to_id' => 'nullable|integer',
            ]);

            SaveAdviseMessage::dispatch(
                session()->getId(),
                isset($data['thread_id']) ? $data['thread_id'] : null,
                $data['role'],
                $data['content'],
                isset($data['input_type']) ? $data['input_type'] : 'text',
                isset($data['reply_to_id']) ? $data['reply_to_id'] : null,
                $request->ip(),
                $request->userAgent(),
                auth()->check() ? auth()->id() : null
            );

            return response()->json([
                'success' => true,
                'queued'  => true,
                'message' => 'Tin nhắn đã được đưa vào hàng đợi lưu trữ.',
            ]);
        } catch (\Throwable $e) {
            Log::error('[saveMessage] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Không thể lưu tin nhắn.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
// 6. Lưu assistant message sau khi stream xong
// ─────────────────────────────────────────────────────────────
public function savePair(Request $request)
{
    try {
        $data = $request->validate([
            'thread_id'         => 'nullable|string|max:128',
            'user_message_id'   => 'nullable|integer',
            'user_content'      => 'required|string',
            'assistant_content' => 'required|string',
            'input_type'        => 'nullable|in:text',
        ]);

        $threadId      = isset($data['thread_id']) ? trim($data['thread_id']) : null;
        $userMessageId = isset($data['user_message_id']) ? (int) $data['user_message_id'] : null;
        $userText      = trim($data['user_content']);
        $rawBot        = trim($data['assistant_content']);
        $inputType     = isset($data['input_type']) ? $data['input_type'] : 'text';

        $session = $this->getOrCreateSession($request, $threadId);

        // 1. Tìm lại user message đã lưu trước
        $userMessage = null;

        if ($userMessageId) {
            $userMessage = AdviseMessage::where('id', $userMessageId)
                ->where('session_id', $session->id)
                ->where('role', 'user')
                ->first();
        }

        // 2. Fallback: phòng trường hợp JS cũ chưa truyền user_message_id
        // Sau khi sửa JS đúng thì bình thường sẽ không chạy vào đoạn này.
        if (!$userMessage) {
            $userMessage = AdviseMessage::create([
                'session_id'  => $session->id,
                'reply_to_id' => null,
                'role'        => 'user',
                'content'     => $userText,
                'input_type'  => $inputType,
                'sent_at'     => now(),
            ]);
        }

        // 3. Xử lý marker tạo ticket / handoff
        $isCreateTicket = $this->shouldCreateTicket($rawBot);
        $needConfirm    = $this->shouldAskTicketConfirm($rawBot);

        $ticketCode = null;
        $botContent = $rawBot;

        if ($isCreateTicket) {
            $ticketCode = $this->generateTicketCode();
            $botContent = $this->ticketCreatedDisplayMessage($ticketCode);
        } elseif ($needConfirm) {
            $botContent = $this->ticketConfirmDisplayMessage(
                $this->removeTicketMarkers($rawBot)
            );
        } else {
            $botContent = $this->removeTicketMarkers($botContent);
        }

        $botMessage = AdviseMessage::create([
            'session_id'  => $session->id,
            'reply_to_id' => $userMessage->id,
            'role'        => 'assistant',
            'content'     => $botContent,
            'input_type'  => $inputType,
            'sent_at'     => now(),
        ]);

        // ĐỒNG BỘ LEAD ACTIVITY: Lưu hoạt động bot trả lời
        $leadId = session('advise_lead_id');
        if (!$leadId) {
            $leadId = $this->extractAndSyncLead($request, $userText, $threadId);
            if ($leadId) {
                session(['advise_lead_id' => $leadId]);
            }
        }
        if ($leadId) {
            $this->logLeadActivity($leadId, 'assistant', $botContent);
        }

        $ticket = null;

        if ($isCreateTicket) {
            $ticketQuestion = $this->extractTicketQuestion($rawBot, $userText);
            $ticketNote     = $this->extractTicketNote($rawBot);

            $ticket = AdviseTicket::create([
                'session_id'      => $session->id,
                'user_message_id' => $userMessage->id,
                'bot_message_id'  => $botMessage->id,
                'thread_id'       => $session->thread_id,
                'ticket_code'     => $ticketCode,
                'question'        => $ticketQuestion,
                'bot_note'        => $ticketNote,
                'status'          => 'pending',
            ]);

            // Cập nhật Lead khi có Ticket (HOT lead cần hỗ trợ gấp)
            if ($leadId) {
                $lead = DB::table('admission_leads')->where('id', $leadId)->first();
                if ($lead) {
                    $newScore = min(100, $lead->score + 35); // Cộng thêm 35 điểm
                    DB::table('admission_leads')->where('id', $leadId)->update([
                        'score' => $newScore,
                        'score_grade' => $newScore >= 80 ? 'hot' : ($newScore >= 50 ? 'warm' : 'cold'),
                        'status' => 'contacted',
                        'note' => trim(($lead->note ? $lead->note . "\n" : "") . "Thí sinh yêu cầu gặp tư vấn viên trực tiếp (Mã Ticket: {$ticketCode})"),
                        'updated_at' => now()
                    ]);

                    // Đẩy tin nhắn thông báo cho hoạt động của Lead
                    $this->logLeadActivity($leadId, 'system', "Tạo yêu cầu hỗ trợ (Ticket: {$ticketCode}). Nội dung: {$ticketQuestion}");
                }
            }


        }

        return response()->json([
            'success'              => true,
            'saved'                => true,
            'is_handoff'           => $isCreateTicket,
            'need_confirm'         => $needConfirm,
            'user_message_id'      => $userMessage->id,
            'assistant_message_id' => $botMessage ? $botMessage->id : null,
            'assistant_content'    => $botContent,
            'ticket'               => $ticket ? [
                'id'          => $ticket->id,
                'ticket_code' => $ticket->ticket_code,
                'status'      => $ticket->status,
            ] : null,
        ]);
    } catch (\Throwable $e) {
        Log::error('[savePair] ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Không thể lưu hội thoại.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    // ─────────────────────────────────────────────────────────────
    // 7. Tra cứu ticket bằng mã
    // ─────────────────────────────────────────────────────────────
    public function lookupTicket(Request $request)
    {
        try {
            $data = $request->validate([
                'ticket_code' => 'required|string|max:32',
            ]);

            $code = strtoupper(trim($data['ticket_code']));

            $ticket = AdviseTicket::where('ticket_code', $code)->first();

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy mã tư vấn.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'ticket'  => $this->formatTicket($ticket),
            ]);
        } catch (\Throwable $e) {
            Log::error('[lookupTicket] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Không thể tra cứu mã tư vấn.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 8. Poll kiểm tra nhân viên đã trả lời ticket chưa
    // ─────────────────────────────────────────────────────────────
    public function checkTicketAnswer(Request $request)
    {
        try {
            $data = $request->validate([
                'thread_id' => 'required|string|max:128',
            ]);

            $ticket = AdviseTicket::where('thread_id', $data['thread_id'])
                ->whereIn('status', ['answered', 'closed'])
                ->latest('updated_at')
                ->first();

            if (!$ticket) {
                return response()->json([
                    'success'    => true,
                    'has_answer' => false,
                    'ticket'     => null,
                ]);
            }

            return response()->json([
                'success'    => true,
                'has_answer' => !empty($ticket->answer),
                'ticket'     => $this->formatTicket($ticket),
            ]);
        } catch (\Throwable $e) {
            Log::error('[checkTicketAnswer] ' . $e->getMessage());

            return response()->json([
                'success'    => false,
                'has_answer' => false,
                'message'    => 'Không thể kiểm tra phản hồi tư vấn.',
                'error'      => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Helper: session
    // ─────────────────────────────────────────────────────────────
    private function getOrCreateSession(Request $request, $threadId = null)
    {
        $session = AdviseSession::firstOrCreate(
            ['session_key' => session()->getId()],
            [
                'thread_id'      => $threadId,
                'user_id'        => auth()->check() ? auth()->id() : null,
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'started_at'     => now(),
                'last_active_at' => now(),
            ]
        );

        $session->update([
            'thread_id'      => $threadId ? $threadId : $session->thread_id,
            'user_id'        => auth()->check() ? auth()->id() : $session->user_id,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'last_active_at' => now(),
        ]);

        if ($threadId) {
            session(['advise_thread_id' => $threadId]);
        }

        return $session;
    }

    // ─────────────────────────────────────────────────────────────
    // Helper: ticket markers
    // ─────────────────────────────────────────────────────────────
    private function shouldCreateTicket($answer)
    {
        $answer = (string) $answer;

        return strpos($answer, '[CREATE_TICKET]') !== false
            || strpos($answer, '[/CREATE_TICKET]') !== false;
    }

    private function shouldAskTicketConfirm($answer)
    {
        $answer = (string) $answer;

        return strpos($answer, '[CAN_HANDOFF]') !== false
            || strpos($answer, '[/CAN_HANDOFF]') !== false;
    }

    private function removeTicketMarkers($answer)
    {
        $answer = (string) $answer;

        $answer = str_replace('[CREATE_TICKET]', '', $answer);
        $answer = str_replace('[/CREATE_TICKET]', '', $answer);
        $answer = str_replace('[CAN_HANDOFF]', '', $answer);
        $answer = str_replace('[/CAN_HANDOFF]', '', $answer);

        return trim($answer);
    }

    private function extractTicketQuestion($assistantContent, $fallbackQuestion)
    {
        $content = $this->removeTicketMarkers($assistantContent);

        if (preg_match('/Câu hỏi cần chuyển\s*:\s*(.+)/iu', $content, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/Cau hoi can chuyen\s*:\s*(.+)/iu', $content, $matches)) {
            return trim($matches[1]);
        }

        return trim($fallbackQuestion);
    }

    private function extractTicketNote($assistantContent)
    {
        $content = $this->removeTicketMarkers($assistantContent);

        if (preg_match('/Lý do\s*:\s*(.+)/isu', $content, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/Ly do\s*:\s*(.+)/isu', $content, $matches)) {
            return trim($matches[1]);
        }

        return trim($content);
    }

    private function ticketConfirmDisplayMessage($reason = '')
    {
        $reason = trim($reason);

        if ($reason !== '') {
            return $reason . "\n\n"
                . "Bạn có muốn tôi chuyển câu hỏi này đến nhân viên tư vấn CTUT để kiểm tra thêm không?\n\n"
                . "Bạn có thể trả lời: **Có, tạo ticket** hoặc **Không**.";
        }

        return "Tôi chưa tìm thấy thông tin này trong dữ liệu tuyển sinh CTUT.\n\n"
            . "Bạn có muốn tôi chuyển câu hỏi này đến nhân viên tư vấn CTUT để kiểm tra thêm không?\n\n"
            . "Bạn có thể trả lời: **Có, tạo ticket** hoặc **Không**.";
    }

    private function generateTicketCode()
    {
        do {
            $code = 'TV' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (AdviseTicket::where('ticket_code', $code)->exists());

        return $code;
    }

    private function ticketCreatedDisplayMessage($ticketCode)
    {
        return "Nhà trường đã ghi nhận câu hỏi và chuyển đến bộ phận tư vấn tuyển sinh.\n\n"
            . "Mã tra cứu của bạn: **{$ticketCode}**\n\n"
            . "Bạn có thể giữ mã này để tra cứu phản hồi sau. Nếu còn mở khung chat, phản hồi của nhân viên sẽ tự hiển thị tại đây.";
    }

    private function formatTicket($ticket)
    {
        return [
            'id'          => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'question'    => $ticket->question,
            'bot_note'    => $ticket->bot_note,
            'answer'      => $ticket->answer,
            'status'      => $ticket->status,
            'answered_at' => $ticket->answered_at
                ? date('d/m/Y H:i', strtotime($ticket->answered_at))
                : null,
            'created_at'  => $ticket->created_at
                ? date('d/m/Y H:i', strtotime($ticket->created_at))
                : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 9. AI Tự Học: Tìm câu hỏi tương tự đã được trả lời
    // ─────────────────────────────────────────────────────────────
    private function findSimilarStaffAnswer($question)
    {
        $question = trim($question);
        if (empty($question)) {
            return null;
        }

        try {
            // 1. Thử tìm bằng Full-Text Search trên chatbot_tickets
            $ticket = DB::table('chatbot_tickets')
                ->where('status', 'answered')
                ->where('is_public', 1)
                ->whereNotNull('staff_answer')
                ->where(function ($q) use ($question) {
                    $q->whereRaw('MATCH(question, staff_answer) AGAINST(? IN NATURAL LANGUAGE MODE)', [$question]);
                })
                ->orderByRaw('MATCH(question, staff_answer) AGAINST(? IN NATURAL LANGUAGE MODE) DESC', [$question])
                ->first();

            if ($ticket) {
                return [
                    'question' => $ticket->question,
                    'answer' => $ticket->staff_answer
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('[findSimilarStaffAnswer] Full-text search failed, falling back to LIKE: ' . $e->getMessage());
        }

        // 2. Fallback: So khớp từ khóa LIKE kiểu cũ (nếu FTS không khớp kết quả nào hoặc gặp lỗi)
        $words = collect(preg_split('/\s+/u', mb_strtolower($question)))
            ->filter(function ($word) {
                return mb_strlen($word) >= 3;
            })
            ->take(8)
            ->values();

        if ($words->isEmpty()) {
            return null;
        }

        $query = DB::table('chatbot_tickets')
            ->where('status', 'answered')
            ->where('is_public', 1)
            ->whereNotNull('staff_answer');

        $query->where(function ($q) use ($words) {
            foreach ($words as $word) {
                $q->orWhere('question', 'like', '%' . $word . '%')
                  ->orWhere('staff_answer', 'like', '%' . $word . '%');
            }
        });

        $tickets = $query->limit(5)->get();

        if ($tickets->isEmpty()) {
            return null;
        }

        $bestTicket = null;
        $maxMatches = 0;

        foreach ($tickets as $ticket) {
            $matches = 0;
            $lowerQuestion = mb_strtolower($ticket->question);
            foreach ($words as $word) {
                if (strpos($lowerQuestion, $word) !== false) {
                    $matches++;
                }
            }

            if ($matches > $maxMatches) {
                $maxMatches = $matches;
                $bestTicket = $ticket;
            }
        }

        if ($bestTicket && $maxMatches >= 1) {
            return [
                'question' => $bestTicket->question,
                'answer' => $bestTicket->staff_answer
            ];
        }

        return null;
    }

    private function anonymizeText($text)
    {
        $text = (string) $text;
        
        // 1. Che SĐT (hỗ trợ cả định dạng +84, khoảng trắng, dấu chấm, dấu gạch ngang, 10-11 số)
        $text = preg_replace('/(?:\+84|0[235789])(?:[\s.-]?\d){8,9}\b/', '[Số điện thoại]', $text);
        
        // 2. Che Email
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[Email]', $text);
        
        // 3. Che số CCCD (12 chữ số) và CMND cũ (9 chữ số)
        $text = preg_replace('/\b(?:\d{12}|\d{9})\b/', '[Số CCCD/CMND]', $text);
        
        return $text;
    }

    // ─────────────────────────────────────────────────────────────
    // 10. Trích xuất thông tin người dùng từ tin nhắn chat & đồng bộ Lead
    // ─────────────────────────────────────────────────────────────
    private function extractAndSyncLead(Request $request, $content, $threadId)
    {
        $phone = null;
        $email = null;
        $fullName = null;

        // Quét số điện thoại (9-11 chữ số)
        if (preg_match('/(0[3|5|7|8|9]+[0-9]{8})\b/', $content, $matches)) {
            $phone = $matches[1];
        }

        // Quét email
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content, $matches)) {
            $email = $matches[0];
        }

        // Quét tên
        if (preg_match('/(?:tên em là|mình tên là|tên tôi là|em tên là|tôi tên là)\s+([\p{L}\s]+)/ui', $content, $matches)) {
            $fullName = trim($matches[1]);
            $fullName = Str::limit($fullName, 100, '');
        }

        if (!$phone) {
            $phone = session('advise_user_phone');
        } else {
            session(['advise_user_phone' => $phone]);
        }

        if (!$email) {
            $email = session('advise_user_email');
        } else {
            session(['advise_user_email' => $email]);
        }

        if (!$fullName) {
            $fullName = session('advise_user_name');
        } else {
            session(['advise_user_name' => $fullName]);
        }

        if ($phone || $email) {
            $lead = null;
            if ($phone) {
                $lead = DB::table('admission_leads')->where('phone', $phone)->first();
            }
            if (!$lead && $email) {
                $lead = DB::table('admission_leads')->where('email', $email)->first();
            }

            $leadData = [
                'full_name' => $fullName ?? ($lead ? $lead->full_name : 'Thí sinh Chatbot'),
                'phone' => $phone ?? ($lead ? $lead->phone : null),
                'email' => $email ?? ($lead ? $lead->email : null),
                'channel' => 'chatbot',
                'last_interaction_at' => now(),
                'updated_at' => now(),
            ];

            $scoringService = app(\App\Services\AdmissionLeadScoringService::class);
            $activities = [['type' => 'message']];

            if ($lead) {
                $leadData['score'] = $lead->score;
                $leadData['score_grade'] = $lead->score_grade;

                $scored = $scoringService->score($leadData, $activities);
                $leadData['score'] = max($lead->score, $scored['score']);
                $leadData['score_grade'] = $scored['grade'];

                DB::table('admission_leads')->where('id', $lead->id)->update($leadData);
                $leadId = $lead->id;
            } else {
                $leadData['status'] = 'new';
                $leadData['created_at'] = now();
                $scored = $scoringService->score($leadData, $activities);
                $leadData['score'] = $scored['score'];
                $leadData['score_grade'] = $scored['grade'];
                $leadData['profile'] = json_encode([
                    'score_reasons' => $scored['reasons'],
                    'first_thread_id' => $threadId
                ]);

                $leadId = DB::table('admission_leads')->insertGetId($leadData);
            }

            return $leadId;
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    // 11. Ghi log hoạt động Lead (Lead Activities)
    // ─────────────────────────────────────────────────────────────
    private function logLeadActivity($leadId, $role, $content, $payload = null)
    {
        if (!$leadId) return;

        DB::table('admission_lead_activities')->insert([
            'lead_id' => $leadId,
            'channel' => 'chatbot',
            'type' => 'message',
            'direction' => $role === 'user' ? 'inbound' : ($role === 'system' ? 'system' : 'outbound'),
            'content' => $content,
            'payload' => $payload ? json_encode($payload) : null,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 13. Index route fallback
    // ─────────────────────────────────────────────────────────────
    public function index()
    {
        return redirect('/');
    }
}