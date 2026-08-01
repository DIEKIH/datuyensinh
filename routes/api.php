<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test-api', function() {
    return response()->json(['status' => 'ok']);
});

use App\Http\Controllers\AdmissionWebhookController;
use App\Http\Middleware\VerifyN8nWebhook;

Route::prefix('n8n')->middleware(['throttle:15,1', VerifyN8nWebhook::class])->group(function () {
    Route::post('/leads', [AdmissionWebhookController::class, 'upsertLead']);
    Route::get('/leads/{id}/status', [AdmissionWebhookController::class, 'checkLeadStatus']);
    Route::post('/rag/answer', [\App\Http\Controllers\AdmissionWebhookController::class, 'ragAnswer']);
    Route::post('/notifications', [\App\Http\Controllers\AdmissionWebhookController::class, 'notification']);
    
    Route::post('/callback', [AdmissionWebhookController::class, 'callback']);
    Route::post('/log', [AdmissionWebhookController::class, 'log']);
});

// Nhan draft tu n8n de cho duyet (Doi ra khoi group n8n vi n8n call api/admission/ai-approvals/draft)
Route::post('/admission/ai-approvals/draft', function (Request $request) {
    $approval = \App\Models\AiApproval::create([
        'customer_name' => $request->input('customer_name', 'Khách hàng'),
        'customer_email' => $request->input('customer_email'),
        'customer_phone' => $request->input('customer_phone'),
        'question' => $request->input('question', ''),
        'ai_answer' => $request->input('ai_answer', ''),
        'channel' => $request->input('channel', 'email'),
        'status' => 'pending'
    ]);
    return response()->json(['success' => true, 'id' => $approval->id]);
});



use App\Http\Controllers\AdmissionScoringController;
    Route::get('/admission/settings', [AdmissionScoringController::class, 'getSettings']);
Route::prefix('admission/scoring')->group(function () {
    Route::get('/criteria', [AdmissionScoringController::class, 'getCriteria']);
    Route::post('/criteria', [AdmissionScoringController::class, 'storeCriterion']);
    Route::put('/criteria/{id}', [AdmissionScoringController::class, 'updateCriterion']);
    Route::delete('/criteria/{id}', [AdmissionScoringController::class, 'deleteCriterion']);
    Route::post('/score', [AdmissionScoringController::class, 'scoreLead']);
    Route::post('/check-admission', [AdmissionScoringController::class, 'checkAdmission']);
});
