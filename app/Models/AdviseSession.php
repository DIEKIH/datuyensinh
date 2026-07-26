<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdviseSession extends Model
{
    protected $table = 'chatbot_sessions';

    public $timestamps = false;

    protected $fillable = [
        'session_key', 'thread_id', 'user_id',
        'ip_address', 'user_agent', 'started_at',
        'last_active_at', 'ended_at',
    ];

    protected $casts = [
        'started_at'    => 'datetime',
        'last_active_at'=> 'datetime',
        'ended_at'      => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AdviseMessage::class, 'session_id');
    }
}
