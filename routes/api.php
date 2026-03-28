<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| AI Chat Routes (Authentication Required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('ai-chat')->name('api.ai-chat.')->group(function () {
    Route::post('/message', [\App\Http\Controllers\API\AIChatController::class, 'sendMessage'])->name('message');
    Route::get('/stream', [\App\Http\Controllers\API\AIChatController::class, 'streamMessage'])->name('stream');
    Route::get('/tools', [\App\Http\Controllers\API\AIChatController::class, 'getTools'])->name('tools');
    Route::get('/history/{conversation_id}', [\App\Http\Controllers\API\AIChatController::class, 'getHistory'])->name('history');
    Route::post('/clear', [\App\Http\Controllers\API\AIChatController::class, 'clearConversation'])->name('clear');
    // AI stats endpoint — returns aggregated statistics for the authenticated user's organization
    Route::get('/stats', [\App\Http\Controllers\API\AIStatsController::class, 'index'])->name('stats');
});
// Auth API (không cần token)
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/register',    [AuthController::class, 'register'])->name('register');
    Route::post('/verify-otp',  [AuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('/login',       [AuthController::class, 'login'])->name('login');
    Route::post('/resend-otp',  [AuthController::class, 'resendOtp'])->name('resend-otp');
});

// Auth API (cần token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',         fn(Request $request) => $request->user());
    Route::get('/auth/me',      [AuthController::class, 'me'])->name('api.auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
});

/*
|--------------------------------------------------------------------------
| Webhook Routes (No Authentication)
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    // GET route for SePay URL verification/testing
    Route::get('/sepay', [WebhookController::class, 'sepayVerify'])->name('sepay.verify');
    // POST route for actual webhook callbacks
    Route::post('/sepay', [WebhookController::class, 'sepay'])->name('sepay');
});

/*
|--------------------------------------------------------------------------
| SePay Webhook Routes (No Authentication)
|--------------------------------------------------------------------------
*/
Route::prefix('sepay')->name('api.sepay.')->group(function () {
    Route::post('/webhook', [\App\Http\Controllers\Api\SepayWebhookController::class, 'handleWebhook'])->name('webhook');
    Route::post('/check-pending', [\App\Http\Controllers\Api\SepayWebhookController::class, 'checkPendingPayments'])->name('check-pending');
});

/*
|--------------------------------------------------------------------------
| Tenant Payment API Routes (Authentication Required)
| Note: These routes have been moved to routes/web.php under tenant group
| to ensure proper session and CSRF handling. Keeping this for backward compatibility.
|--------------------------------------------------------------------------
*/

