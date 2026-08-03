<?php

namespace App\Jobs;

use App\Models\AdviseMessage;
use App\Models\AdviseSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SaveAdviseMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 5;

    private $sessionKey;
    private $conversationId;
    private $role;
    private $content;
    private $inputType;
    private $replyToId;
    private $ipAddress;
    private $userAgent;
    private $userId;

    public function __construct(
        $sessionKey,
        $conversationId,
        $role,
        $content,
        $inputType = 'text',
        $replyToId = null,
        $ipAddress = null,
        $userAgent = null,
        $userId = null
    ) {
        $this->sessionKey = $sessionKey;
        $this->conversationId = $conversationId;
        $this->role = $role;
        $this->content = $content;
        $this->inputType = $inputType;
        $this->replyToId = $replyToId;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->userId = $userId;
    }

    public function handle()
    {
        $conversationId = trim((string) $this->conversationId);

        /*
         * Controller và Job phải tạo cùng một session_key.
         * Mỗi conversation có một phiên CSDL riêng, dù cùng trình duyệt.
         */
        $conversationSessionKey = hash(
            'sha256',
            (string) $this->sessionKey
                . '|'
                . $conversationId
        );

        $conversationReference = $conversationId !== ''
            ? hash('sha256', $conversationId)
            : null;

        $session = AdviseSession::firstOrCreate(
            ['session_key' => $conversationSessionKey],
            [
                'thread_id' => $conversationReference,
                'user_id' => $this->userId,
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'started_at' => now(),
                'last_active_at' => now(),
            ]
        );

        $session->update([
            'thread_id' => $conversationReference
                ?: $session->thread_id,
            'user_id' => $this->userId
                ?: $session->user_id,
            'ip_address' => $this->ipAddress
                ?: $session->ip_address,
            'user_agent' => $this->userAgent
                ?: $session->user_agent,
            'last_active_at' => now(),
        ]);

        AdviseMessage::create([
            'session_id' => $session->id,
            'reply_to_id' => $this->replyToId,
            'role' => $this->role,
            'content' => $this->content,
            'input_type' => $this->inputType,
            'sent_at' => now(),
        ]);
    }

    public function failed($e)
    {
        Log::error('[SaveAdviseMessage] Job thất bại', [
            'session_key' => $this->sessionKey,
            'conversation_id' => $this->conversationId,
            'role' => $this->role,
            'error' => $e->getMessage(),
        ]);
    }
}
