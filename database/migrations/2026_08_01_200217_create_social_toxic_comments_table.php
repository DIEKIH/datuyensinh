<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialToxicCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_toxic_comments', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->default('facebook');
            $table->string('comment_id')->index();
            $table->string('post_id')->nullable();
            $table->string('sender_id')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('message');
            $table->string('sentiment_category'); // 'negative', 'toxic'
            $table->text('ai_reason')->nullable(); // AI explanation for classification
            $table->string('status')->default('pending'); // 'pending', 'ignored', 'deleted', 'blocked'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_toxic_comments');
    }
}
