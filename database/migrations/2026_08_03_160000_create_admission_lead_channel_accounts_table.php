<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'admission_lead_channel_accounts',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('lead_id');

                $table->string('channel', 50);

                $table->string('account_id', 191);

                $table->dateTime(
                    'last_interaction_at'
                )->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'channel',
                        'account_id',
                    ],
                    'lead_channel_account_unique'
                );

                $table->index(
                    'lead_id',
                    'lead_channel_account_lead_id_index'
                );

                $table->index(
                    'channel',
                    'lead_channel_account_channel_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'admission_lead_channel_accounts'
        );
    }
};