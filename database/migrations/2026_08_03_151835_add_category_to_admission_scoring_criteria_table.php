<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryToAdmissionScoringCriteriaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admission_scoring_criteria', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->after('id')->comment('Gom nhóm tiêu chí (VD: Tương tác mạng xã hội)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admission_scoring_criteria', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
}
