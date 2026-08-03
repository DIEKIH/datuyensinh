<?php
// database/migrations/2026_08_03_160000_create_admission_lead_channel_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_lead_channel_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('admission_leads')->cascadeOnDelete();
            $table->string('channel', 40); // facebook, zalo, website, tiktok...
            $table->string('account_id'); // fb sender_id / zalo uid / sđt (form)
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'account_id']);
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_lead_channel_accounts');
    }
};