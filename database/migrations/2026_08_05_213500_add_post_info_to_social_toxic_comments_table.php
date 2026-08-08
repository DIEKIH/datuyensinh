<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPostInfoToSocialToxicCommentsTable extends Migration
{
    public function up()
    {
        Schema::table('social_toxic_comments', function (Blueprint $table) {
            $table->text('post_message')
                ->nullable()
                ->after('post_id');

            $table->string('post_url', 1000)
                ->nullable()
                ->after('post_message');
        });
    }

    public function down()
    {
        Schema::table('social_toxic_comments', function (Blueprint $table) {
            $table->dropColumn([
                'post_message',
                'post_url',
            ]);
        });
    }
}