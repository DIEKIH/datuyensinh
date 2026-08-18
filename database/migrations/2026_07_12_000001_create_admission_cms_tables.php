<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmissionCmsTables extends Migration
{
    public function up()
    {
        Schema::create('admission_leads', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('channel', 80)->default('manual')->index();
            $table->string('source_campaign')->nullable()->index();
            $table->string('intended_major')->nullable()->index();
            $table->string('province')->nullable();
            $table->string('status', 40)->default('new')->index();
            $table->unsignedSmallInteger('score')->default(0)->index();
            $table->string('score_grade', 20)->default('cold')->index();
            $table->json('profile')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('last_interaction_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('admission_lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('admission_leads')->cascadeOnDelete();
            $table->string('channel', 80)->default('cms')->index();
            $table->string('type', 80)->default('message')->index();
            $table->string('external_id')->nullable()->index();
            $table->string('direction', 20)->default('inbound');
            $table->text('content')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });

        // Schema::create('admission_rag_documents', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('title');
        //     $table->string('category', 80)->default('quy_che')->index();
        //     $table->string('status', 30)->default('active')->index();
        //     $table->string('source_url')->nullable();
        //     $table->longText('content');
        //     $table->timestamps();
        // });

        // Schema::create('admission_rag_chunks', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('document_id')->constrained('admission_rag_documents')->cascadeOnDelete();
        //     $table->unsignedInteger('chunk_index')->default(0);
        //     $table->string('title')->nullable();
        //     $table->text('content');
        //     $table->timestamps();
        //     $table->index(['document_id', 'chunk_index']);
        // });

        Schema::create('admission_n8n_logs', function (Blueprint $table) {
            $table->id();
            $table->string('workflow', 120)->nullable()->index();
            $table->string('event_type', 120)->nullable()->index();
            $table->string('status', 30)->default('received')->index();
            $table->json('payload')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admission_n8n_logs');
        Schema::dropIfExists('admission_rag_chunks');
        Schema::dropIfExists('admission_rag_documents');
        Schema::dropIfExists('admission_lead_activities');
        Schema::dropIfExists('admission_leads');
    }
}
