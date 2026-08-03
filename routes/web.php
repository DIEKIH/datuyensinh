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
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// //Dashboard
// Route::prefix('/')->group(function(){
//     Route::get('/login',[LoginController::class,'index'])->name('login');
// });


// //Dashboard
// Route::prefix('/')->group(function(){
//     Route::get('/dashboard',[DashboardController::class,'dashboard'])->name('dashboard');
// });



// //Users
// Route::prefix('users')->group(function(){
//     Route::get('/',[UsersController::class,'index'])->name('users');
// });




// //Menu
// Route::prefix('menu')->group(function(){
//     Route::get('/',[MenuController::class,'index'])->name('menu');
// });







// //Homepage
// Route::prefix('homepage')->group(function(){
//     Route::get('/',[HomepageController::class,'index'])->name('homepage');
// });




Route::get('/', function () {
    return view('users.pages.index');
});


// //Posts
// Route::prefix('posts')->group(function(){
//     Route::get('/',[PostsController::class,'index'])->name('posts');
// });

// Route::get('/{slug}', [MenuController::class, 'show'])->where('slug', '[a-zA-Z0-9-_]+')->name('page.show');

// mới
// removed the duplicate view-based login route in favor of controller-based login

// Form login
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');

// Xử lý login
Route::post('/login', [LoginController::class, 'login']);

// Logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin routes
// Route::prefix('admin')->group(function () {
//     Route::get('/dashboard', [AdminController::class, 'dashboard']);
//     Route::get('/baiviet', [AdminController::class, 'baiviet']);
//     Route::get('/tatcabaiviet', [AdminController::class, 'tatcabaiviet']);
//     Route::post('/store', [AdminController::class, 'store'])->name('baiviet.store');
//     Route::get('/menus', [AdminController::class, 'getMenus']);
//     Route::get('/danhmuc', [AdminController::class, 'getDanhmuc']);
//     Route::get('/tacgia', [AdminController::class, 'getTacgia']);
//     Route::get('baiviet/{id}', [AdminController::class, 'getBaiviet']);
//     Route::delete('baiviet/{id}', [AdminController::class, 'destroy']);
//     // Route::post('/suggest', [AdminController::class, 'suggest'])->name('admin.suggest');
// });

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/dashboard/stats', [AdminController::class, 'dashboardStats']);
    Route::get('/advise',                     [AdminController::class, 'advise']);
    Route::get('/advise/stats',               [AdminController::class, 'adviseStats']);
    Route::get('/advise/sessions',            [AdminController::class, 'adviseSessions']);
    Route::get('/advise/sessions/{id}',       [AdminController::class, 'adviseSessionDetail']);
    Route::delete('/advise/sessions/{id}',    [AdminController::class, 'adviseDestroySession']);
    Route::delete('/advise/messages/{id}',    [AdminController::class, 'adviseDestroyMessage']);
    Route::delete('/advise/all',              [AdminController::class, 'adviseDestroyAll']);
    Route::get('/advise/tickets', [AdminController::class, 'adviseTickets'])
        ->name('admin.advise.tickets');

    Route::get('/advise/tickets/{id}', [AdminController::class, 'adviseTicketShow'])
        ->name('admin.advise.tickets.show');

    Route::post('/advise/tickets/{id}/answer', [AdminController::class, 'adviseTicketAnswer'])
        ->name('admin.advise.tickets.answer');

    Route::post('/advise/tickets/{id}/close', [AdminController::class, 'adviseTicketClose'])
        ->name('admin.advise.tickets.close');

    Route::get('/baiviet', [AdminController::class, 'baiviet']);
    Route::get('/tatcabaiviet', [AdminController::class, 'tatcabaiviet']);
    Route::get('/baiviet-theo-danhmuc', [AdminController::class, 'baivietTheoDanhmuc']);
    Route::post('/store', [AdminController::class, 'store'])->name('baiviet.store');
    Route::get('/menus', [AdminController::class, 'getMenus']);
    Route::get('/danhmuc', [AdminController::class, 'getDanhmuc']);

    Route::get('baiviet/{id}', [AdminController::class, 'getBaiviet']);
    Route::delete('baiviet/{id}', [AdminController::class, 'destroy']);

    Route::get('/tacgia', [AdminController::class, 'tacgia'])->name('tacgia');
    Route::get('/tacgia/list', [AdminController::class, 'getTacgia']);
    Route::get('/tacgia_danhsach', [AdminController::class, 'tacgia_danhsach']);
    Route::get('tacgia_them', [AdminController::class, 'tacgia_them']);
    Route::get('/tacgia_load', [AdminController::class, 'tacgia_load']);
    Route::get('/tacgia_capnhat', [AdminController::class, 'tacgia_capnhat']);
    Route::get('/tacgia_xoa', [AdminController::class, 'tacgia_xoa']);


    Route::get('/banner', [AdminController::class, 'banner']);
    Route::get('/tatcabanner', [AdminController::class, 'tatcabanner']);
    Route::post('/banner/store', [AdminController::class, 'bannerStore']);
    Route::delete('/banner/{id}', [AdminController::class, 'bannerDestroy']);



    Route::get('/nganhhoc', [AdminController::class, 'nganhhoc'])->name('admin.nganhhoc');
    Route::get('/nganhhoc/list', [AdminController::class, 'nganhhocList']);
    Route::get('/nganhhoc/khoa', [AdminController::class, 'nganhhocKhoa']);
    Route::post('/nganhhoc/store', [AdminController::class, 'nganhhocStore']);
    Route::get('/nganhhoc/tohop-list', [AdminController::class, 'nganhhocToHopList']);
    Route::get('/nganhhoc/{id}', [AdminController::class, 'getNganhhoc']);
    Route::delete('/nganhhoc/{id}', [AdminController::class, 'nganhhocDestroy']);

    Route::get('/highlight-stats',          [AdminController::class, 'highlightStats']);
    Route::get('/highlight-stats/list',     [AdminController::class, 'highlightStatsList']);
    Route::post('/highlight-stats/store',   [AdminController::class, 'highlightStatsStore']);
    Route::get('/highlight-stats/{id}',     [AdminController::class, 'highlightStatsGet']);
    Route::delete('/highlight-stats/{id}',  [AdminController::class, 'highlightStatsDestroy']);

    Route::get('/admission-cms', [AdmissionAdminController::class, 'index']);
    Route::get('/admission-cms/stats', [AdmissionAdminController::class, 'stats']);
    Route::get('/admission-cms/leads', [AdmissionAdminController::class, 'leads']);
    Route::post('/admission-cms/leads', [AdmissionAdminController::class, 'storeLead']);
    Route::get('/admission-cms/leads/{id}', [AdmissionAdminController::class, 'showLead']);
    Route::post('/admission-cms/leads/{id}/status', [AdmissionAdminController::class, 'updateLeadStatus']);
    Route::get('/admission-cms/documents', [AdmissionAdminController::class, 'documents']);
    Route::post('/admission-cms/documents', [AdmissionAdminController::class, 'storeDocument']);
    Route::post('/admission-cms/rag/ask', [AdmissionAdminController::class, 'askRag']);
    Route::post('/admission-cms/nurture', [AdmissionAdminController::class, 'triggerNurture']);

    // Human-in-the-Loop AI Approvals
    Route::get('/admission-cms/approvals', [AdmissionAdminController::class, 'listApprovals']);
    Route::post('/admission-cms/approvals/{id}/action', [AdmissionAdminController::class, 'handleApprovalAction']);

    // Toxic Comments
    Route::get('/admission-cms/toxic-comments', [AdmissionAdminController::class, 'listToxicComments']);
    Route::post('/admission-cms/toxic-comments/{id}/action', [AdmissionAdminController::class, 'handleToxicCommentAction']);

    // Social Posts
    Route::get('/admission-cms/social-posts', [AdmissionAdminController::class, 'listSocialPosts']);

    Route::get('/admission-cms/openai/config', [AdmissionAdminController::class, 'getOpenAiConfig']);
    Route::post('/admission-cms/openai/prompt', [AdmissionAdminController::class, 'updateOpenAiPrompt']);
    Route::post('/admission-cms/openai/files', [AdmissionAdminController::class, 'uploadOpenAiFile']);
    Route::delete('/admission-cms/openai/files/{fileId}', [AdmissionAdminController::class, 'deleteOpenAiFile']);

    // Scoring Criteria
    Route::get('/admission-cms/scoring/criteria', [\App\Http\Controllers\AdmissionScoringController::class, 'getCriteria']);
    Route::post('/admission-cms/scoring/criteria', [\App\Http\Controllers\AdmissionScoringController::class, 'storeCriterion']);
    Route::put('/admission-cms/scoring/criteria/{id}', [\App\Http\Controllers\AdmissionScoringController::class, 'updateCriterion']);
    Route::delete('/admission-cms/scoring/criteria/{id}', [\App\Http\Controllers\AdmissionScoringController::class, 'deleteCriterion']);
});






Route::post('/baiviet/{id}/view', function ($id) {
    DB::table('baiviet')->where('id',$id)->increment('views');
    return response()->json(['success'=>true]);
});
Route::get('/menus', [Trangchu::class, 'getMenus']);
Route::post('/upload-image', [AdminController::class, 'uploadImage']);
Route::post('/upload-video', [AdminController::class, 'uploadVideo']);


// Route::get('/tacgia', [AdminController::class, 'tacgia'])->name('tacgia');
// Route::get('/tacgia_danhsach', [AdminController::class, 'tacgia_danhsach']);
// Route::get('tacgia_them', [AdminController::class, 'tacgia_them']);
// Route::get('/tacgia_load', [AdminController::class, 'tacgia_load']);
// Route::get('/tacgia_capnhat', [AdminController::class, 'tacgia_capnhat']);
// Route::get('/tacgia_xoa', [AdminController::class, 'tacgia_xoa']);

// Index routes
Route::get('/index', [Trangchu::class, 'index']);
Route::get('/banners', [Trangchu::class, 'banners']);
Route::get('/tintucnoibat', [Trangchu::class, 'tintucnoibat']);
Route::get('/tintucnho', [Trangchu::class, 'tintucnho']);
Route::get('/sukiennho', [Trangchu::class, 'sukiennho']);
Route::get('/thongbaonho', [Trangchu::class, 'thongbaonho']);
Route::get('/nganhnho', [Trangchu::class, 'nganhnho']);

Route::post('/admin/suggest', [AdminController::class, 'suggest'])->name('admin.suggest');

// Route::get('/tuyen-sinh', [AdviseController::class, 'index'])->name('advise.index');
// Route::post('/tuyen-sinh/chat', [AdviseController::class, 'chat'])->name('advise.chat');

// Advise
// Route::post('/tuyen-sinh/chat',         [AdviseController::class, 'chat'])->name('advise.chat');
// Route::post('/tuyen-sinh/chat/reset',   [AdviseController::class, 'resetThread'])->name('advise.reset');
// Route::get('/tuyen-sinh/advise',       [AdviseController::class, 'index'])->name('advise.index');
// Route::post('/tuyen-sinh/chat/stream', [AdviseController::class, 'stream'])
//      ->name('advise.stream');

// Route::post('/tuyen-sinh/chat/save',       [AdviseController::class, 'saveMessage'])->name('advise.save');       // MỚI — lưu DB qua queue
// Route::post('/tuyen-sinh/chat/transcribe', [AdviseController::class, 'transcribe'])->name('advise.transcribe'); // MỚI — Whisper STT


// Advise - OpenAI Responses API
Route::get(
    '/tuyen-sinh/advise',
    [AdviseController::class, 'index']
)->name('advise.index');

Route::post(
    '/advise/conversation',
    [AdviseController::class, 'createConversation']
)->name('advise.conversation');

Route::post(
    '/advise/message',
    [AdviseController::class, 'addMessage']
)->name('advise.message');

Route::post(
    '/tuyen-sinh/chat/stream',
    [AdviseController::class, 'stream']
)->name('advise.stream');

Route::post(
    '/tuyen-sinh/chat/reset',
    [AdviseController::class, 'resetConversation']
)->name('advise.reset');

Route::post(
    '/tuyen-sinh/chat/save',
    [AdviseController::class, 'saveMessage']
)->name('advise.save');

Route::post(
    '/advise/save-pair',
    [AdviseController::class, 'savePair']
)->name('advise.savePair');

Route::get(
    '/advise/tickets/check',
    [AdviseController::class, 'checkTicketAnswer']
)->name('advise.tickets.check');

Route::get(
    '/advise/tickets/lookup',
    [AdviseController::class, 'lookupTicket']
)->name('advise.tickets.lookup');

Route::get('/visitor/stats', [Trangchu::class, 'visitorStats']);


Route::get('/highlight-stats', [Trangchu::class, 'highlightStats']);

Route::get('/gioithieu', function () {
    return view('users.pages.gioithieu');
});

Route::get('/dang-ky-tu-van', [AdmissionLeadController::class, 'create'])
    ->name('admission.leads.create');
Route::post('/dang-ky-tu-van', [AdmissionLeadController::class, 'store'])
    ->name('admission.leads.store');

Route::post('/dang-ky-tu-van-n8n', [\App\Http\Controllers\AdmissionN8nLeadController::class, 'store'])
    ->name('admission.leads.store');

// User routes
Route::get('/nganh/{slug}', [Trangchu::class, 'nganhChitiet'])
    ->name('nganh.chitiet');

Route::get('/auth/facebook', [FacebookAuthController::class, 'login'])
    ->name('facebook.login');

Route::get('/auth/facebook/callback', [FacebookAuthController::class, 'callback'])
    ->name('facebook.callback');

Route::post('/facebook/post', [FacebookAuthController::class, 'postToFacebook'])
    ->name('facebook.post');

Route::post('/facebook/deauthorize', [FacebookAuthController::class, 'deauthorize'])
    ->name('facebook.deauthorize');

Route::post('/facebook/data-deletion', [FacebookAuthController::class, 'dataDeletion'])
    ->name('facebook.data-deletion');

Route::get('/facebook/deletion-status/{id}', function($id) {
    return response()->json(['status' => 'pending', 'confirmation_code' => $id]);
})->name('facebook.deletion.status');


Route::get('/{slug}', [MenuController::class, 'show'])
    ->where('slug', '.*')  // chấp nhận nhiều đoạn có dấu gạch chéo
    ->name('page.show');
