<?php

use App\Http\Controllers\AdmissionScoringController;
use App\Http\Controllers\AdmissionWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Thêm vào routes/api.php
|--------------------------------------------------------------------------
| AdmissionWebhookController tự kiểm tra header X-N8N-Token.
*/
Route::prefix('n8n')->group(function () {
    Route::post('/rag/answer', [AdmissionWebhookController::class, 'ragAnswer']);
    Route::post('/leads/upsert', [AdmissionWebhookController::class, 'upsertLead']);
    Route::get('/leads/{id}/status', [AdmissionWebhookController::class, 'checkLeadStatus']);
    Route::post('/leads/{id}/nurture', [AdmissionWebhookController::class, 'sendNurtureEmail']);
    Route::post('/notifications', [AdmissionWebhookController::class, 'notification']);
    Route::post('/callback', [AdmissionWebhookController::class, 'callback']);
    Route::post('/log', [AdmissionWebhookController::class, 'log']);
});

/*
|--------------------------------------------------------------------------
| API chấm điểm lead
|--------------------------------------------------------------------------
| Nên đặt các route quản trị tiêu chí/ngưỡng trong middleware auth của admin.
*/
Route::prefix('admission/scoring')->group(function () {
    Route::post('/score', [AdmissionScoringController::class, 'scoreLead']);
    Route::post('/check-admission', [AdmissionScoringController::class, 'checkAdmission']);

    Route::get('/criteria', [AdmissionScoringController::class, 'getCriteria']);
    Route::post('/criteria', [AdmissionScoringController::class, 'storeCriterion']);
    Route::put('/criteria/{id}', [AdmissionScoringController::class, 'updateCriterion']);
    Route::delete('/criteria/{id}', [AdmissionScoringController::class, 'deleteCriterion']);

    Route::get('/threshold', [AdmissionScoringController::class, 'getThreshold']);
    Route::post('/threshold', [AdmissionScoringController::class, 'saveThreshold']);
});
