<?php

namespace App\Http\Controllers;

use App\Jobs\SaveAdviseMessage;
use App\Models\AdviseMessage;
use App\Models\AdviseSession;
use App\Models\AdviseTicket;
use App\Services\ChatbotAnswerLibraryService;
use App\Services\ChatbotQueryAnalyzerService;
use App\Services\AdviseConversationContextService;
use App\Services\ChatbotTicketCommandService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class AdviseController extends Controller
{
    private const OAI_BASE = 'https://api.openai.com/v1';

    private $answerLibrary;
    private $queryAnalyzer;
    private $conversationContext;
    private $ticketCommands;

    public function __construct(
        ChatbotAnswerLibraryService $answerLibrary,
        ChatbotQueryAnalyzerService $queryAnalyzer,
        AdviseConversationContextService $conversationContext,
        ChatbotTicketCommandService $ticketCommands
    ) {
        $this->answerLibrary = $answerLibrary;
        $this->queryAnalyzer = $queryAnalyzer;
        $this->conversationContext = $conversationContext;
        $this->ticketCommands = $ticketCommands;
    }

    private function oaiHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . config('services.openai.key'),
            'Content-Type'  => 'application/json',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 1. Tạo OpenAI Conversation
    // ─────────────────────────────────────────────────────────────
    public function createConversation()
    {
        try {
            $conversationId = 'adv_' . str_replace(
                '-',
                '',
                (string) Str::uuid()
            );

            session([
                'advise_conversation_id' => $conversationId,
            ]);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversationId,
                'id' => $conversationId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[createConversation] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo phiên chat.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

   // ─────────────────────────────────────────────────────────────
// 2. Lưu user message vào CSDL trước
// ─────────────────────────────────────────────────────────────
    public function addMessage(Request $request)
    {
        try {
            $data = $request->validate([
                'conversation_id' => 'required|string|max:128',
                'content'         => 'required|string|max:12000',
            ]);

            $conversationId = trim($data['conversation_id']);
            $content = trim($data['content']);

            if ($content === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Nội dung tin nhắn không được để trống.',
                ], 422);
            }

            session([
                'advise_conversation_id' => $conversationId,
            ]);

            $session = $this->getOrCreateSession(
                $request,
                $conversationId
            );

            // Giữ nguyên nghiệp vụ đồng bộ Lead.
            $leadId = $this->extractAndSyncLead(
                $request,
                $content,
                $conversationId
            );

            if ($leadId) {
                session(['advise_lead_id' => $leadId]);
                $this->logLeadActivity($leadId, 'user', $content);
            }

            // Responses API nhận nội dung ở stream().
            // Tại đây chỉ lưu câu hỏi vào CSDL nội bộ.
            $userMessage = AdviseMessage::create([
                'session_id'  => $session->id,
                'reply_to_id' => null,
                'role'        => 'user',
                'content'     => $content,
                'input_type'  => 'text',
                'sent_at'     => now(),
            ]);

            return response()->json([
                'success'         => true,
                'message'         => 'Đã lưu câu hỏi.',
                'conversation_id' => $conversationId,
                'session_id'      => $session->id,
                'user_message_id' => $userMessage->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('[addMessage] ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể lưu câu hỏi.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 3. Stream Responses API
    // ─────────────────────────────────────────────────────────────
    public function stream(Request $request)
    {
        try {
            $data = $request->validate([
                'conversation_id' => 'required|string|max:128',
                'user_message_id' => 'nullable|integer',
                'content'         => 'required|string|max:12000',
            ]);

            $conversationId = trim($data['conversation_id']);
            $content = trim($data['content']);

            if ($content === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Nội dung tin nhắn không được để trống.',
                ], 422);
            }

            session([
                'advise_conversation_id' => $conversationId,
            ]);

            $userMessage = null;

            if (!empty($data['user_message_id'])) {
                $userMessage = AdviseMessage::find(
                    (int) $data['user_message_id']
                );
            }

            /*
             * ƯU TIÊN CAO NHẤT: lệnh chủ động tạo ticket.
             *
             * Không cho lệnh điều khiển này đi qua kho câu trả lời,
             * nếu không một câu cũ như “danh sách ngành” có thể bị lấy lại.
             */
            $ticketCommand = $this->ticketCommands->analyze(
                $content
            );

            if ($ticketCommand['is_request']) {
                $previousQuestion = $this->conversationContext
                    ->previousMeaningfulUserQuestion(
                        $userMessage,
                        $this->ticketCommands
                    );

                $ticketQuestion = $this->ticketCommands
                    ->resolveQuestion(
                        $ticketCommand,
                        $userMessage,
                        $previousQuestion
                    );

                $this->clearPendingHandoff();

                if ($ticketQuestion === '') {
                    return $this->localSseResponse(
                        $this->ticketCommands
                            ->clarificationMessage()
                    );
                }

                return $this->localSseResponse(
                    $this->ticketCommands->buildCreateMarker(
                        $ticketQuestion
                    )
                );
            }

            /*
             * Nếu chatbot đang chờ xác nhận chuyển nhân viên:
             * - “có”, “ok”, “tạo đi” dùng câu hỏi gốc đã lưu;
             * - câu hỏi mới sẽ hủy trạng thái chờ cũ;
             * - không gửi câu xác nhận sang kho/OpenAI.
             */
            if (session('advise_handoff_pending')) {
                if ($this->isNegativeTicketConfirmation($content)) {
                    $this->clearPendingHandoff();

                    return $this->localSseResponse(
                        'Đã hủy yêu cầu chuyển đến nhân viên tư vấn. '
                        . 'Bạn có thể tiếp tục đặt câu hỏi khác.'
                    );
                }

                if ($this->isPositiveTicketConfirmation($content)) {
                    $question = trim((string) session(
                        'advise_handoff_question'
                    ));
                    $note = trim((string) session(
                        'advise_handoff_note'
                    ));

                    if ($question === '') {
                        $question = $this->conversationContext
                            ->previousMeaningfulUserQuestion(
                                $userMessage,
                                $this->ticketCommands
                            );
                    }

                    if ($question === '') {
                        return $this->localSseResponse(
                            $this->ticketCommands
                                ->clarificationMessage()
                        );
                    }

                    return $this->localSseResponse(
                        $this->ticketCommands->buildCreateMarker(
                            $question,
                            $note !== ''
                                ? $note
                                : 'Người dùng đồng ý chuyển câu hỏi '
                                    . 'đến nhân viên tư vấn.'
                        )
                    );
                }

                if ($this->looksLikeNewQuestion($content)) {
                    $this->clearPendingHandoff();
                } else {
                    return $this->localSseResponse(
                        'Bạn vui lòng trả lời **Có, tạo ticket** '
                        . 'hoặc **Không**.'
                    );
                }
            }

            /*
             * Exact answer đã được admin duyệt phải thắng
             * phản hồi làm rõ và OpenAI.
             */
            $exactLibraryAnswer = $this->answerLibrary
                ->findExactApprovedAnswer($content);

            if ($exactLibraryAnswer) {
                $exactAnswer = trim(strip_tags(
                    $exactLibraryAnswer['answer']
                ));

                if (
                    $exactAnswer !== ''
                    && $this->answerLibrary
                        ->isAnswerSafeForDelivery($exactAnswer)
                ) {
                    $this->answerLibrary->markUsed(
                        $exactLibraryAnswer['library_id']
                    );

                    Log::info(
                        '[stream] Reused exact approved answer',
                        [
                            'library_id' =>
                                $exactLibraryAnswer['library_id'],
                            'match_type' =>
                                $exactLibraryAnswer['match_type'],
                            'question' => $content,
                        ]
                    );

                    return $this->localSseResponse(
                        $exactAnswer,
                        [
                            'answer_source' => 'library',
                            'answer_library_id' =>
                                $exactLibraryAnswer['library_id'],
                            'library_source_type' =>
                                $exactLibraryAnswer['source_type'],
                        ]
                    );
                }
            }

            $analysis = $this->queryAnalyzer->analyze($content);
            $previousAnalysis = $this->conversationContext
                ->previousUserAnalysis($userMessage);
            $localResponse = $this->queryAnalyzer->localResponse(
                $analysis,
                $previousAnalysis,
                [
                    'has_recent_assistant_question' =>
                        $this->conversationContext
                            ->hasRecentAssistantQuestion(
                                $userMessage
                            ),
                ]
            );

            if ($localResponse !== null) {
                return $this->localSseResponse($localResponse);
            }

            /*
             * Kiểm tra kho câu trả lời trước OpenAI.
             *
             * Kho là lớp tối ưu, tuyệt đối không được làm endpoint 500.
             * Luôn dùng nội dung hiện tại nếu user_message_id có sự cố.
             */
            /*
             * Request hiện tại là nguồn sự thật.
             */
            $libraryQuestion = $content;

            $previousQuestion = $userMessage
                ? $this->conversationContext
                    ->previousUserQuestion($userMessage)
                : null;

            $similarAnswer = null;

            try {
                $similarAnswer = $this->answerLibrary
                    ->findApprovedAnswer(
                        $libraryQuestion,
                        [
                            'previous_question' =>
                                $previousQuestion,
                        ]
                    );
            } catch (\Throwable $libraryError) {
                Log::error(
                    '[stream] Answer library failed open',
                    [
                        'question' => $libraryQuestion,
                        'error' =>
                            $libraryError->getMessage(),
                        'exception' => $libraryError,
                    ]
                );

                $similarAnswer = null;
            }

            if ($similarAnswer) {
                $answer = trim(strip_tags(
                    $similarAnswer['answer']
                ));

                if (
                    $answer === ''
                    || !$this->answerLibrary
                        ->isAnswerSafeForDelivery($answer)
                ) {
                    $this->answerLibrary->deleteEntry(
                        $similarAnswer['library_id'],
                        'blocked_before_delivery'
                    );

                    $similarAnswer = null;
                }

                if ($similarAnswer && $answer !== '') {
                    $this->answerLibrary->markUsed(
                        $similarAnswer['library_id']
                    );

                    Log::info(
                        '[stream] Reused approved local answer',
                        [
                            'library_id' =>
                                $similarAnswer['library_id'],
                            'source_type' =>
                                $similarAnswer['source_type'],
                            'score' =>
                                $similarAnswer['score'],
                            'match_type' => isset(
                                $similarAnswer['match_type']
                            )
                                ? $similarAnswer['match_type']
                                : null,
                            'match_details' => isset(
                                $similarAnswer['match_details']
                            )
                                ? $similarAnswer['match_details']
                                : [],
                            'question' => $libraryQuestion,
                        ]
                    );

                    return $this->localSseResponse(
                        $answer,
                        [
                            'answer_source' => 'library',
                            'answer_library_id' =>
                                $similarAnswer['library_id'],
                            'library_source_type' =>
                                $similarAnswer['source_type'],
                        ]
                    );
                }
            }

            $settings = $this->openAiSettings(true);

            /*
             * Toàn bộ prompt được quản lý trong:
             * storage/app/openai/advise_instructions.txt
             *
             * Controller không ghép thêm quy tắc trả lời vào code.
             */
            $instructions = $settings['instructions'];

            $payload = [
                'model' => $settings['model'],
                'instructions' => $instructions,
                /*
                 * Tự quản lý ngữ cảnh từ CSDL để mọi lượt local/library/
                 * ticket đều nằm trong cùng một lịch sử nhất quán.
                 */
                'input' => $this->conversationContext
                    ->buildResponseInput(
                        $userMessage,
                        $content
                    ),
                'tools' => [[
                    'type'             => 'file_search',
                    'vector_store_ids' => [
                        $settings['vector_store_id'],
                    ],
                    'max_num_results' => $settings['max_num_results'],
                ]],
                'tool_choice' => 'required',
                'stream'      => true,
                'store'       => true,
            ];

            $response = Http::withHeaders(array_merge(
                $this->oaiHeaders(),
                ['Accept' => 'text/event-stream']
            ))
                ->withOptions([
                    'stream'          => true,
                    'timeout'         => 120,
                    'connect_timeout' => 15,
                ])
                ->post(self::OAI_BASE . '/responses', $payload);

            if (!$response->successful()) {
                Log::error('[stream] Responses API error', [
                    'conversation_id' => $conversationId,
                    'status'          => $response->status(),
                    'body'            => $response->body(),
                    'request_id'      => $response->header('x-request-id'),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Hệ thống AI chưa thể tạo phản hồi.',
                ], 502);
            }

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
        } catch (\Throwable $e) {
            Log::error('[stream] ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể nhận phản hồi từ hệ thống AI.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Reset Conversation
    // ─────────────────────────────────────────────────────────────
    public function resetConversation()
    {
        session()->forget([
            'advise_conversation_id',
            'advise_lead_id',
        ]);
        $this->clearPendingHandoff();

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
                'conversation_id' => 'nullable|string|max:128',
                'role'            => 'required|in:user,assistant',
                'content'         => 'required|string',
                'input_type'      => 'nullable|in:text',
                'reply_to_id'     => 'nullable|integer',
            ]);

            SaveAdviseMessage::dispatch(
                session()->getId(),
                isset($data['conversation_id'])
                    ? $data['conversation_id']
                    : null,
                $data['role'],
                $data['content'],
                isset($data['input_type'])
                    ? $data['input_type']
                    : 'text',
                isset($data['reply_to_id'])
                    ? $data['reply_to_id']
                    : null,
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
                'conversation_id'   => 'nullable|string|max:128',
                'user_message_id'   => 'nullable|integer',
                'user_content'      => 'required|string',
                'assistant_content' => 'required|string',
                'input_type'        => 'nullable|in:text',
                'answer_source'     => 'nullable|in:ai,library,system',
                'answer_library_id' => 'nullable|integer|min:1',
            ]);

            $conversationId = isset($data['conversation_id'])
                ? trim($data['conversation_id'])
                : null;
            $userMessageId = isset($data['user_message_id'])
                ? (int) $data['user_message_id']
                : null;
            $userText = trim($data['user_content']);
            $rawBot = trim($data['assistant_content']);
            $inputType = isset($data['input_type'])
                ? $data['input_type']
                : 'text';
            $answerSource = isset($data['answer_source'])
                ? $data['answer_source']
                : 'ai';
            $answerLibraryId = isset($data['answer_library_id'])
                ? (int) $data['answer_library_id']
                : null;

            $session = $this->getOrCreateSession(
                $request,
                $conversationId
            );

            $userMessage = null;

            if ($userMessageId) {
                $userMessage = AdviseMessage::where('id', $userMessageId)
                    ->where('session_id', $session->id)
                    ->where('role', 'user')
                    ->first();
            }

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

            $isCreateTicket = $this->shouldCreateTicket($rawBot);
            $needConfirm = $this->shouldAskTicketConfirm($rawBot);

            $ticket = null;
            $ticketCode = null;
            $ticketQuestion = null;
            $ticketNote = null;
            $ticketUserMessage = $userMessage;

            if ($isCreateTicket) {
                $pendingMessageId = (int) session(
                    'advise_handoff_user_message_id',
                    0
                );
                $pendingQuestion = trim((string) session(
                    'advise_handoff_question'
                ));
                $pendingNote = trim((string) session(
                    'advise_handoff_note'
                ));

                if ($pendingMessageId > 0) {
                    $originalMessage = AdviseMessage::where(
                        'id',
                        $pendingMessageId
                    )
                        ->where('session_id', $session->id)
                        ->where('role', 'user')
                        ->first();

                    if ($originalMessage) {
                        $ticketUserMessage = $originalMessage;
                    }
                }

                /*
                 * Thứ tự lấy nội dung ticket:
                 * 1. Câu hỏi gốc của luồng xác nhận đang chờ.
                 * 2. Câu hỏi nằm trong marker [CREATE_TICKET].
                 * 3. Tin nhắn người dùng hiện tại.
                 *
                 * Nhờ đó câu:
                 * “Tạo ticket với câu hỏi: em ăn cơm chưa”
                 * sẽ tạo đúng nội dung “em ăn cơm chưa”, không lấy câu trước.
                 */
                $markerQuestion = $this->extractTicketQuestion(
                    $rawBot,
                    ''
                );

                $ticketQuestion = $pendingQuestion !== ''
                    ? $pendingQuestion
                    : (
                        $markerQuestion !== ''
                            ? $markerQuestion
                            : trim(
                                (string) $ticketUserMessage->content
                            )
                    );

                $markerNote = $this->extractTicketNote($rawBot);

                $ticketNote = $pendingNote !== ''
                    ? $pendingNote
                    : (
                        $markerNote !== ''
                            ? $markerNote
                            : 'Người dùng yêu cầu nhân viên tư vấn hỗ trợ.'
                    );

                $ticket = AdviseTicket::where(
                    'session_id',
                    $session->id
                )
                    ->where(
                        'user_message_id',
                        $ticketUserMessage->id
                    )
                    ->first();

                if ($ticket) {
                    $ticketCode = $ticket->ticket_code;
                } else {
                    $ticketCode = $this->generateTicketCode();
                }
            }

            $botContent = $rawBot;

            if ($isCreateTicket) {
                $botContent = $this->ticketCreatedDisplayMessage(
                    $ticketCode
                );
            } elseif ($needConfirm) {
                $ticketQuestion = trim((string) $userMessage->content);
                $ticketNote = $this->extractTicketNote($rawBot);

                session([
                    'advise_handoff_pending' => true,
                    'advise_handoff_question' => $ticketQuestion,
                    'advise_handoff_note' => $ticketNote,
                    'advise_handoff_user_message_id' => $userMessage->id,
                ]);

                $cleanBotContent = $this->removeTicketMarkers($rawBot);

                if (
                    strpos($rawBot, '[CAN_HANDOFF]') !== false
                    || strpos($rawBot, '[/CAN_HANDOFF]') !== false
                ) {
                    $botContent = $this->ticketConfirmDisplayMessage(
                        $cleanBotContent
                    );
                } else {
                    /*
                     * Prompt đã tạo sẵn câu hỏi xác nhận tự nhiên.
                     * Không bọc thêm lần nữa để tránh lặp nội dung.
                     */
                    $botContent = $cleanBotContent;
                }
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

            /*
             * Câu AI chỉ được đưa vào kho khi admin tick tại màn hình
             * quản lý phiên chat. Không tự lưu câu chưa được chọn.
             */
            $storedAnswerLibraryId = $answerLibraryId;

            $leadId = session('advise_lead_id');

            if (!$leadId) {
                $leadId = $this->extractAndSyncLead(
                    $request,
                    $userText,
                    $conversationId
                );

                if ($leadId) {
                    session(['advise_lead_id' => $leadId]);
                }
            }

            if ($leadId) {
                $this->logLeadActivity(
                    $leadId,
                    'assistant',
                    $botContent
                );
            }

            if ($isCreateTicket) {
                if (!$ticket) {
                    $ticket = AdviseTicket::create([
                        'session_id'      => $session->id,
                        'user_message_id' => $ticketUserMessage->id,
                        'bot_message_id'  => $botMessage->id,
                        'thread_id'       => $session->thread_id,
                        'ticket_code'     => $ticketCode,
                        'question'        => $ticketQuestion,
                        'bot_note'        => $ticketNote,
                        'status'          => 'pending',
                    ]);

                    if ($leadId) {
                        $lead = DB::table('admission_leads')
                            ->where('id', $leadId)
                            ->first();

                        if ($lead) {
                            $newScore = min(100, $lead->score + 35);

                            DB::table('admission_leads')
                                ->where('id', $leadId)
                                ->update([
                                    'score' => $newScore,
                                    'score_grade' => $newScore >= 80
                                        ? 'hot'
                                        : ($newScore >= 50
                                            ? 'warm'
                                            : 'cold'),
                                    'status' => 'contacted',
                                    'note' => trim(
                                        ($lead->note
                                            ? $lead->note . "\n"
                                            : '')
                                        . 'Thí sinh yêu cầu gặp tư vấn viên '
                                        . 'trực tiếp (Mã Ticket: '
                                        . $ticketCode
                                        . ')'
                                    ),
                                    'updated_at' => now(),
                                ]);

                            $this->logLeadActivity(
                                $leadId,
                                'system',
                                'Tạo yêu cầu hỗ trợ (Ticket: '
                                . $ticketCode
                                . '). Nội dung: '
                                . $ticketQuestion
                            );
                        }
                    }
                }

                $this->clearPendingHandoff();
            }

            return response()->json([
                'success'              => true,
                'saved'                => true,
                'is_handoff'           => $isCreateTicket,
                'need_confirm'         => $needConfirm,
                'user_message_id'      => $userMessage->id,
                'assistant_message_id' => $botMessage->id,
                'assistant_content'    => $botContent,
                'answer_source'        => $answerSource,
                'answer_library_id'    => $storedAnswerLibraryId,
                'ticket'               => $ticket ? [
                    'id'          => $ticket->id,
                    'ticket_code' => $ticket->ticket_code,
                    'status'      => $ticket->status,
                ] : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[savePair] ' . $e->getMessage(), [
                'exception' => $e,
            ]);

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
                'conversation_id' => 'required|string|max:128',
                'ack_ticket_id' => 'nullable|integer|min:1',
            ]);

            $conversationReference = $this->conversationReference(
                $data['conversation_id']
            );

            /*
             * Chỉ đánh dấu đã gửi sau khi JavaScript xác nhận đã render.
             * Nếu response đầu tiên bị mất mạng, ticket vẫn được trả lại ở
             * lần poll sau thay vì mất vĩnh viễn.
             */
            if (!empty($data['ack_ticket_id'])) {
                AdviseTicket::where(
                    'thread_id',
                    $conversationReference
                )
                    ->where('id', (int) $data['ack_ticket_id'])
                    ->whereNotNull('staff_answer')
                    ->update([
                        'delivered_at' => now(),
                        'updated_at' => now(),
                    ]);

                return response()->json([
                    'success' => true,
                    'acknowledged' => true,
                    'has_answer' => false,
                    'ticket' => null,
                ]);
            }

            $ticket = AdviseTicket::where(
                'thread_id',
                $conversationReference
            )
                ->whereIn('status', ['answered', 'closed'])
                ->whereNotNull('staff_answer')
                ->whereNull('delivered_at')
                ->latest('updated_at')
                ->first();

            if (!$ticket) {
                return response()->json([
                    'success' => true,
                    'has_answer' => false,
                    'ticket' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'has_answer' => true,
                'ticket' => $this->formatTicket($ticket),
            ]);
        } catch (\Throwable $e) {
            Log::error('[checkTicketAnswer] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'has_answer' => false,
                'message' => 'Không thể kiểm tra phản hồi tư vấn.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Helper: session
    // ─────────────────────────────────────────────────────────────

    private function localClarificationFor($text)
    {
        $analysis = $this->queryAnalyzer->analyze($text);

        return $this->queryAnalyzer->localResponse($analysis);
    }

    private function buildResponseInput(
        $currentUserMessage,
        $fallbackContent
    ) {
        return $this->conversationContext->buildResponseInput(
            $currentUserMessage,
            $fallbackContent
        );
    }

    private function openAiSettings($requireKnowledgeBase = true)
    {
        $key = trim((string) config('services.openai.key'));
        $model = trim((string) config('services.openai.model'));
        $vectorStoreId = trim((string) config(
            'services.openai.vector_store_id'
        ));
        $instructions = $this->loadInstructions();
        $maxNumResults = (int) config(
            'services.openai.max_num_results',
            5
        );

        if ($key === '') {
            throw new RuntimeException('Thiếu OPENAI_API_KEY.');
        }

        if ($requireKnowledgeBase) {
            if ($model === '') {
                throw new RuntimeException('Thiếu OPENAI_MODEL.');
            }

            if ($vectorStoreId === '') {
                throw new RuntimeException(
                    'Thiếu OPENAI_VECTOR_STORE_ID.'
                );
            }

            if ($instructions === '') {
                throw new RuntimeException(
                    'Thiếu nội dung hướng dẫn chatbot OpenAI.'
                );
            }
        }

        if ($maxNumResults < 1 || $maxNumResults > 50) {
            $maxNumResults = 5;
        }

        return [
            'model'           => $model,
            'vector_store_id' => $vectorStoreId,
            'instructions'    => $instructions,
            'max_num_results' => $maxNumResults,
        ];
    }

    private function loadInstructions()
    {
        $path = trim((string) config(
            'services.openai.instructions_file'
        ));

        if ($path === '') {
            throw new RuntimeException(
                'Thiếu cấu hình services.openai.instructions_file.'
            );
        }

        if (!is_file($path)) {
            throw new RuntimeException(
                'Không tìm thấy file prompt OpenAI: ' . $path
            );
        }

        $content = file_get_contents($path);

        if ($content === false || trim($content) === '') {
            throw new RuntimeException(
                'File prompt OpenAI đang trống hoặc không đọc được.'
            );
        }

        $instructions = trim($content);
        $addendumPath = trim((string) config(
            'services.openai.instructions_addendum_file',
            storage_path(
                'app/openai/advise_instructions_addendum.txt'
            )
        ));

        if ($addendumPath !== '' && is_file($addendumPath)) {
            $addendum = file_get_contents($addendumPath);

            if ($addendum !== false && trim($addendum) !== '') {
                $instructions .= "\n\n" . trim($addendum);
            }
        }

        return $instructions;
    }

    private function conversationReference($conversationId)
    {
        $conversationId = trim((string) $conversationId);

        if ($conversationId === '') {
            return null;
        }

        return hash('sha256', $conversationId);
    }

    private function getOrCreateSession(
        Request $request,
        $conversationId = null
    ) {
        $conversationReference = $this->conversationReference(
            $conversationId
        );

        $conversationSessionKey = hash(
            'sha256',
            session()->getId()
                . '|'
                . trim((string) $conversationId)
        );

        $session = AdviseSession::firstOrCreate(
            ['session_key' => $conversationSessionKey],
            [
                // Không đổi CSDL: thread_id vẫn là VARCHAR(64).
                'thread_id'      => $conversationReference,
                'user_id'        => auth()->check() ? auth()->id() : null,
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'started_at'     => now(),
                'last_active_at' => now(),
            ]
        );

        $session->update([
            'thread_id' => $conversationReference
                ? $conversationReference
                : $session->thread_id,
            'user_id' => auth()->check()
                ? auth()->id()
                : $session->user_id,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'last_active_at' => now(),
        ]);

        if ($conversationId) {
            session([
                'advise_conversation_id' => $conversationId,
            ]);
        }

        return $session;
    }

    private function normalizeConfirmationText($text)
    {
        $text = mb_strtolower(trim((string) $text), 'UTF-8');
        $text = Str::ascii($text);
        $text = preg_replace('/[^a-z0-9\s]+/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function isPositiveTicketConfirmation($text)
    {
        $text = $this->normalizeConfirmationText($text);

        if ($text === '') {
            return false;
        }

        $wordCount = count(preg_split('/\s+/', $text));

        if ($wordCount > 7) {
            return false;
        }

        return preg_match(
            '/^(co|co a|co nhe|co nha|co tao ticket|co tao di|'
            . 'co toa di|tao ticket|tao di|toa di|dong y|ok|'
            . 'okay|duoc|chuyen di|gui di|yes|uh|u|'
            . 'co chuyen di|co gui di)$/',
            $text
        ) === 1;
    }

    private function isNegativeTicketConfirmation($text)
    {
        $text = $this->normalizeConfirmationText($text);

        if ($text === '') {
            return false;
        }

        $wordCount = count(preg_split('/\s+/', $text));

        if ($wordCount > 7) {
            return false;
        }

        return preg_match(
            '/^(khong|khong can|thoi|huy|bo qua|khong tao|'
            . 'khong chuyen|no)(\s.*)?$/',
            $text
        ) === 1;
    }

    private function looksLikeNewQuestion($text)
    {
        $text = trim((string) $text);

        if ($text === '') {
            return false;
        }

        if (strpos($text, '?') !== false) {
            return true;
        }

        $analysis = $this->queryAnalyzer->analyze($text);

        if ($analysis['primary_intent'] !== 'unknown') {
            return true;
        }

        if (in_array(
            $analysis['normalized'],
            [
                'an',
                'an uong',
                'do an',
                'o',
                'cho o',
                'diem',
                'nganh',
                'hoc phi',
                'ho so',
            ],
            true
        )) {
            return true;
        }

        return mb_strlen($text, 'UTF-8') >= 8;
    }

    private function clearPendingHandoff()
    {
        session()->forget([
            'advise_handoff_pending',
            'advise_handoff_question',
            'advise_handoff_note',
            'advise_handoff_user_message_id',
        ]);
    }

    private function localSseResponse($text, array $context = [])
    {
        $text = (string) $text;
        $answerSource = isset($context['answer_source'])
            ? (string) $context['answer_source']
            : 'system';
        $answerLibraryId = isset($context['answer_library_id'])
            ? (int) $context['answer_library_id']
            : null;
        $librarySourceType = isset($context['library_source_type'])
            ? (string) $context['library_source_type']
            : null;

        return response()->stream(function () use (
            $text,
            $answerSource,
            $answerLibraryId,
            $librarySourceType
        ) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', false);

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            $answerContext = [
                'type' => 'response.answer_context',
                'answer_source' => $answerSource,
                'answer_library_id' => $answerLibraryId,
                'library_source_type' => $librarySourceType,
            ];

            $delta = [
                'type' => 'response.output_text.delta',
                'delta' => $text,
            ];

            $done = [
                'type' => 'response.output_text.done',
                'text' => $text,
            ];

            $completed = [
                'type' => 'response.completed',
                'response' => [
                    'id' => 'resp_local_' . Str::random(16),
                    'status' => 'completed',
                ],
            ];

            echo "event: response.answer_context\n";
            echo 'data: ' . json_encode(
                $answerContext,
                JSON_UNESCAPED_UNICODE
            ) . "\n\n";

            echo "event: response.output_text.delta\n";
            echo 'data: ' . json_encode(
                $delta,
                JSON_UNESCAPED_UNICODE
            ) . "\n\n";

            echo "event: response.output_text.done\n";
            echo 'data: ' . json_encode(
                $done,
                JSON_UNESCAPED_UNICODE
            ) . "\n\n";

            echo "event: response.completed\n";
            echo 'data: ' . json_encode(
                $completed,
                JSON_UNESCAPED_UNICODE
            ) . "\n\n";

            @ob_flush();
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
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
        $answer = trim((string) $answer);

        if (
            strpos($answer, '[CAN_HANDOFF]') !== false
            || strpos($answer, '[/CAN_HANDOFF]') !== false
        ) {
            return true;
        }

        /*
         * Prompt hiện không dùng [CAN_HANDOFF], vì vậy Laravel nhận diện
         * trực tiếp câu hỏi xác nhận chuyển nhân viên để lưu trạng thái chờ.
         */
        return preg_match(
            '/bạn có muốn.*(?:chuyển|gửi).*(?:nhân viên|tư vấn viên)|'
            . 'bạn có muốn.*tạo.*ticket|'
            . 'có muốn tôi chuyển câu hỏi này.*tư vấn/iu',
            $answer
        ) === 1;
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

        if (preg_match(
            '/Câu hỏi cần chuyển\s*:\s*(.+?)(?:\r?\n|$)/iu',
            $content,
            $matches
        )) {
            return trim($matches[1]);
        }

        if (preg_match(
            '/Cau hoi can chuyen\s*:\s*(.+?)(?:\r?\n|$)/iu',
            $content,
            $matches
        )) {
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
        'answer'      => $ticket->staff_answer,
        'status'      => $ticket->status,
        'answered_at' => $ticket->answered_at
            ? date(
                'd/m/Y H:i',
                strtotime($ticket->answered_at)
            )
            : null,
        'created_at' => $ticket->created_at
            ? date(
                'd/m/Y H:i',
                strtotime($ticket->created_at)
            )
            : null,
    ];
}

    // ─────────────────────────────────────────────────────────────
    // 9. Bảo vệ dữ liệu trước khi tái sử dụng nội dung
    // ─────────────────────────────────────────────────────────────
    private function anonymizeText($text)
    {
        return $this->queryAnalyzer->redactSensitiveData($text);
    }

    private function extractAndSyncLead(Request $request, $content, $conversationId)
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
                    'first_thread_id' => $conversationId
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