<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdviseTicket extends Model
{
    protected $table = 'chatbot_tickets';

    protected $fillable = [
        'session_id',
        'user_message_id',
        'bot_message_id',
        'thread_id',
        'ticket_code',
        'question',
        'bot_note',
        'status',
        'answered_by',
        'staff_answer',
        'answered_at',
        'delivered_at',
    ];

    public function session()
    {
        return $this->belongsTo(AdviseSession::class, 'session_id');
    }

    public function answeredBy()
    {
        return $this->belongsTo(Nguoidung::class, 'answered_by');
    }
}