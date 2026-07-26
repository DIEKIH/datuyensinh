<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPublicToChatbotTicketsTable extends Migration
{
    public function up()
    {
        Schema::table('chatbot_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('chatbot_tickets', 'is_public')) {
                $table->boolean('is_public')->default(0)->after('status')->index();
            }
        });
    }

    public function down()
    {
        Schema::table('chatbot_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('chatbot_tickets', 'is_public')) {
                $table->dropColumn('is_public');
            }
        });
    }
}
