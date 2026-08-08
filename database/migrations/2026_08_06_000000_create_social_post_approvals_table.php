<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialPostApprovalsTable extends Migration
{
    public function up()
    {
        Schema::create('social_post_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('approval_key', 191)->unique();
            $table->string('workflow_id', 100)->nullable();
            $table->string('execution_id', 100)->nullable();
            $table->string('article_id', 100)->nullable();
            $table->string('title', 500);
            $table->text('article_url')->nullable();
            $table->longText('caption')->nullable();
            $table->text('hashtags')->nullable();
            $table->json('content_images')->nullable();
            $table->json('platforms')->nullable();
            $table->json('ai_review')->nullable();
            $table->text('resume_url');
            $table->string('status', 20)->default('pending');
            $table->text('decision_note')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('article_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_post_approvals');
    }
}
