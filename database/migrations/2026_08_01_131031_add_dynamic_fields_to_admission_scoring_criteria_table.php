<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_scoring_criteria', function (Blueprint $table) {
            $table->string('input_type', 50)->default('text')->after('data_field');
            $table->json('options')->nullable()->after('input_type');
            $table->boolean('show_on_public_form')->default(false)->after('options');
            $table->boolean('is_required')->default(false)->after('show_on_public_form');
        });
    }

    public function down(): void
    {
        Schema::table('admission_scoring_criteria', function (Blueprint $table) {
            $table->dropColumn(['input_type', 'options', 'show_on_public_form', 'is_required']);
        });
    }
};
