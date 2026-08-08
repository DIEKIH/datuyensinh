<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFacebookHideResultToSocialToxicCommentsTable extends Migration
{
    public function up()
    {
        Schema::table('social_toxic_comments', function (Blueprint $table) {
            $table->string('facebook_hide_status', 30)
                ->default('unknown')
                ->after('ai_reason');

            $table->boolean('facebook_hidden')
                ->nullable()
                ->after('facebook_hide_status');

            $table->boolean('facebook_can_hide')
                ->nullable()
                ->after('facebook_hidden');

            $table->text('facebook_hide_error')
                ->nullable()
                ->after('facebook_can_hide');

            $table->timestamp('facebook_hide_attempted_at')
                ->nullable()
                ->after('facebook_hide_error');
        });
    }

    public function down()
    {
        Schema::table('social_toxic_comments', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_hide_status',
                'facebook_hidden',
                'facebook_can_hide',
                'facebook_hide_error',
                'facebook_hide_attempted_at',
            ]);
        });
    }
}
