<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class AdviseMessage extends Model
{
    protected $table = 'chatbot_messages';

    public $timestamps = false;

    protected $fillable = [
        'session_id', 'reply_to_id', 'role',
        'content', 'input_type','sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /** Phiên cha */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AdviseSession::class, 'session_id');
    }

    /** Tin nhắn cha (đệ quy) */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AdviseMessage::class, 'reply_to_id');
    }

    /** Các tin nhắn con (đệ quy) */
    public function replies(): HasMany
    {
        return $this->hasMany(AdviseMessage::class, 'reply_to_id');
    }

    /** Lấy toàn bộ cây con đệ quy */
    public function allReplies(): HasMany
    {
        return $this->replies()->with('allReplies');
    }
}