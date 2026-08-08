<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialPostApproval extends Model
{
    protected $table = 'social_post_approvals';

    protected $fillable = [
        'approval_key',
        'workflow_id',
        'execution_id',
        'article_id',
        'title',
        'article_url',
        'caption',
        'hashtags',
        'content_images',
        'platforms',
        'ai_review',
        'resume_url',
        'status',
        'decision_note',
        'decided_by',
        'decided_at',
    ];

    protected $hidden = [
        'resume_url',
    ];

    protected $casts = [
        'content_images' => 'array',
        'platforms' => 'array',
        'ai_review' => 'array',
        'decided_at' => 'datetime',
    ];
}
