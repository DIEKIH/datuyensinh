<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_scoring_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('criterion_code')->unique();
            $table->string('criterion_name');
            $table->text('description')->nullable();
            $table->string('data_field');
            $table->string('operator');
            $table->string('comparison_value')->nullable();
            $table->integer('score')->default(0);
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('admission_scoring_thresholds', function (Blueprint $table) {
            $table->id();
            $table->integer('hot_lead_min_score')->default(80);
            $table->integer('warm_lead_min_score')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('admission_scoring_thresholds')->insert([
            'hot_lead_min_score' => 80,
            'warm_lead_min_score' => 50,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_scoring_thresholds');
        Schema::dropIfExists('admission_scoring_criteria');
    }
};