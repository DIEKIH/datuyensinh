<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (!Schema::hasTable('admission_lead_score_logs')) {
    Schema::create('admission_lead_score_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lead_id')->constrained('admission_leads')->cascadeOnDelete();
        $table->foreignId('channel_account_id')->nullable()->constrained('admission_lead_channel_accounts')->nullOnDelete();
        $table->foreignId('criterion_id')->nullable()->constrained('admission_scoring_criteria')->nullOnDelete();
        $table->string('action_type', 50)->nullable()->index(); // like, share, comment, form_submit
        $table->integer('score_added')->default(0);
        $table->string('source_channel', 50)->nullable(); // facebook, zalo, web
        $table->string('external_id')->nullable()->index(); // ID cua comment/post de chong trung
        $table->timestamps();
    });
    echo "Table admission_lead_score_logs created successfully.\n";
} else {
    echo "Table already exists.\n";
}
