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

    // Không khai báo type — tương thích PHP 7.2+
    private $sessionKey;
    private $threadId;
    private $role;
    private $content;
    private $inputType;
    private $replyToId;
    private $ipAddress;
    private $userAgent;
    private $userId;

    public function __construct(
        $sessionKey,
        $threadId,
        $role,
        $content,
        $inputType = 'text',
        $replyToId = null,
        $ipAddress = null,
        $userAgent = null,
        $userId = null
    ) {
        $this->sessionKey = $sessionKey;
        $this->threadId   = $threadId;
        $this->role       = $role;
        $this->content    = $content;
        $this->inputType  = $inputType;
        $this->replyToId  = $replyToId;
        $this->ipAddress  = $ipAddress;
        $this->userAgent  = $userAgent;
        $this->userId     = $userId;
    }

    public function handle()
    {
        // 1. Tạo hoặc lấy phiên chat
        $session = AdviseSession::firstOrCreate(
            ['session_key' => $this->sessionKey],
            [
                'thread_id'      => $this->threadId,
                'user_id'        => $this->userId,
                'ip_address'     => $this->ipAddress,
                'user_agent'     => $this->userAgent,
                'started_at'     => now(),
                'last_active_at' => now(),
            ]
        );

        // 2. Cập nhật phiên
        $session->update([
            'thread_id'      => $this->threadId ? $this->threadId : $session->thread_id,
            'user_id'        => $this->userId ? $this->userId : $session->user_id,
            'ip_address'     => $this->ipAddress ? $this->ipAddress : $session->ip_address,
            'user_agent'     => $this->userAgent ? $this->userAgent : $session->user_agent,
            'last_active_at' => now(),
        ]);

        // 3. Lưu tin nhắn phụ
        AdviseMessage::create([
            'session_id'  => $session->id,
            'reply_to_id' => $this->replyToId,
            'role'        => $this->role,
            'content'     => $this->content,
            'input_type'  => $this->inputType,
            'sent_at'     => now(),
        ]);
    }

    public function failed($e)
    {
        Log::error('[SaveAdviseMessage] Job thất bại', [
            'session_key' => $this->sessionKey,
            'thread_id'   => $this->threadId,
            'role'        => $this->role,
            'error'       => $e->getMessage(),
        ]);
    }
}