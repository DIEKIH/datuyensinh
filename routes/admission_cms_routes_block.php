<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Admin\DashboardController;
// use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\PostsController;
use App\Http\Controllers\Admin\UsersController;
// use App\Http\Controllers\Admin\LoginController;

use App\Http\Controllers\MenuController;
use App\Http\Controllers\Trangchu;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AdviseController;
use App\Http\Controllers\AdmissionAdminController;
use App\Http\Controllers\AdmissionLeadController;
use App\Http\Controllers\FacebookAuthController;
/*
     * ==========================================================
     * CMS TUYỂN SINH - CÁC TRANG GIAO DIỆN
     * ==========================================================
     */
    Route::get('/admission-cms', [AdmissionAdminController::class, 'index'])
        ->name('admin.admission-cms.dashboard');

    Route::get('/admission-cms/lead-management', [AdmissionAdminController::class, 'leadManagement'])
        ->name('admin.admission-cms.leads');

    Route::get('/admission-cms/ai-approvals', [AdmissionAdminController::class, 'approvalManagement'])
        ->name('admin.admission-cms.approvals');

    Route::get('/admission-cms/openai-management', [AdmissionAdminController::class, 'openAiManagement'])
        ->name('admin.admission-cms.openai');

    Route::get('/admission-cms/scoring-management', [AdmissionAdminController::class, 'scoringManagement'])
        ->name('admin.admission-cms.scoring');

    Route::get('/admission-cms/social-warnings', [AdmissionAdminController::class, 'toxicManagement'])
        ->name('admin.admission-cms.toxic');

    Route::get('/admission-cms/social-post-history', [AdmissionAdminController::class, 'socialPostManagement'])
        ->name('admin.admission-cms.social-posts');

    Route::get('/admission-cms/n8n-monitoring', [AdmissionAdminController::class, 'n8nMonitoring'])
        ->name('admin.admission-cms.n8n');

    /*
     * ==========================================================
     * CMS TUYỂN SINH - API NỘI BỘ GIỮ NGUYÊN
     * ==========================================================
     */
    Route::get('/admission-cms/stats', [AdmissionAdminController::class, 'stats']);

    Route::get('/admission-cms/leads', [AdmissionAdminController::class, 'leads']);
    Route::post('/admission-cms/leads', [AdmissionAdminController::class, 'storeLead']);
    Route::get('/admission-cms/leads/{id}', [AdmissionAdminController::class, 'showLead']);
    Route::post('/admission-cms/leads/{id}/status', [AdmissionAdminController::class, 'updateLeadStatus']);

    Route::get('/admission-cms/documents', [AdmissionAdminController::class, 'documents']);
    Route::post('/admission-cms/documents', [AdmissionAdminController::class, 'storeDocument']);
    Route::post('/admission-cms/rag/ask', [AdmissionAdminController::class, 'askRag']);
    Route::post('/admission-cms/nurture', [AdmissionAdminController::class, 'triggerNurture']);

    Route::get('/admission-cms/approvals', [AdmissionAdminController::class, 'listApprovals']);
    Route::post('/admission-cms/approvals/{id}/action', [AdmissionAdminController::class, 'handleApprovalAction']);

    Route::get('/admission-cms/toxic-comments', [AdmissionAdminController::class, 'listToxicComments']);
    Route::post('/admission-cms/toxic-comments/{id}/action', [AdmissionAdminController::class, 'handleToxicCommentAction']);

    Route::get('/admission-cms/social-posts', [AdmissionAdminController::class, 'listSocialPosts']);

    Route::get('/admission-cms/openai/config', [AdmissionAdminController::class, 'getOpenAiConfig']);
    Route::post('/admission-cms/openai/prompt', [AdmissionAdminController::class, 'updateOpenAiPrompt']);
    Route::post('/admission-cms/openai/files', [AdmissionAdminController::class, 'uploadOpenAiFile']);
    Route::delete('/admission-cms/openai/files/{fileId}', [AdmissionAdminController::class, 'deleteOpenAiFile']);

    Route::get('/admission-cms/n8n/logs', [AdmissionAdminController::class, 'n8nLogs']);

    Route::get('/admission-cms/scoring/criteria', [\App\Http\Controllers\AdmissionScoringController::class, 'getCriteria']);
    Route::post('/admission-cms/scoring/criteria', [\App\Http\Controllers\AdmissionScoringController::class, 'storeCriterion']);
    Route::put('/admission-cms/scoring/criteria/{id}', [\App\Http\Controllers\AdmissionScoringController::class, 'updateCriterion']);
    Route::delete('/admission-cms/scoring/criteria/{id}', [\App\Http\Controllers\AdmissionScoringController::class, 'deleteCriterion']);
