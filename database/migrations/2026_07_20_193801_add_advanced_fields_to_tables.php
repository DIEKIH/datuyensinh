<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdvancedFieldsToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('baiviet', function (Blueprint $table) {
            $table->boolean('auto_publish')->default(false)->after('id');
        });

        Schema::table('admission_lead_activities', function (Blueprint $table) {
            $table->string('sentiment', 20)->nullable()->after('direction');
            $table->text('media_url')->nullable()->after('content');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('baiviet', function (Blueprint $table) {
            $table->dropColumn('auto_publish');
        });

        Schema::table('admission_lead_activities', function (Blueprint $table) {
            $table->dropColumn(['sentiment', 'media_url']);
        });
    }
}
