<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Office\OfficeAttachmentController;
use App\Http\Controllers\Api\V1\Office\OfficeChatController;
use App\Http\Controllers\Api\V1\Office\OfficeDirectoryController;
use App\Http\Controllers\Api\V1\ParishApprovalController;
use App\Http\Controllers\Api\V1\Parishes\AnnouncementController;
use App\Http\Controllers\Api\V1\Parishes\EngagementController;
use App\Http\Controllers\Api\V1\Parishes\MassController;
use App\Http\Controllers\Api\V1\Parishes\NewsController;
use App\Http\Controllers\Api\V1\Parishes\ParishController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Publiczny endpoint kontraktu API pozostaje bez autoryzacji.
    Route::get('/openapi.yaml', function () {
        return response()->file(base_path('openapi/v1.yaml'), [
            'Content-Type' => 'application/yaml; charset=utf-8',
        ]);
    });

    Route::get('/meta/service-version', [MetaController::class, 'serviceVersion']);

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:api-auth-register');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-auth-login');
        Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:api-auth-refresh');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:api-auth-forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:api-auth-reset-password');
        Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware('signed')
            ->whereNumber('id')
            ->name('api.v1.auth.verify-email');

        Route::middleware('api.auth')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationNotification'])
                ->middleware('throttle:api-auth-verification-resend');
        });
    });

    Route::get('/parishes', [ParishController::class, 'index']);
    Route::get('/parishes/{parishId}', [ParishController::class, 'show'])->whereNumber('parishId');
    Route::get('/parishes/{parishId}/home-feed', [ParishController::class, 'homeFeed'])->whereNumber('parishId');

    Route::get('/parishes/{parishId}/masses', [MassController::class, 'index'])->whereNumber('parishId');
    Route::get('/parishes/{parishId}/masses/recent-past', [MassController::class, 'recentPast'])->whereNumber('parishId');
    Route::get('/parishes/{parishId}/masses/upcoming', [MassController::class, 'upcoming'])->whereNumber('parishId');
    Route::get('/parishes/{parishId}/masses/{massId}', [MassController::class, 'show'])->whereNumber('parishId')->whereNumber('massId');

    Route::get('/parishes/{parishId}/announcements/current', [AnnouncementController::class, 'current'])->whereNumber('parishId');
    Route::get('/parishes/{parishId}/announcements', [AnnouncementController::class, 'index'])->whereNumber('parishId');
    Route::get('/parishes/{parishId}/announcements/{packageId}/pdf', [AnnouncementController::class, 'pdf'])
        ->whereNumber('parishId')
        ->whereNumber('packageId')
        ->name('api.v1.announcements.pdf');

    Route::get('/parishes/{parishId}/news', [NewsController::class, 'index'])->whereNumber('parishId');
    Route::get('/parishes/{parishId}/news/{newsId}', [NewsController::class, 'show'])->whereNumber('parishId')->whereNumber('newsId');
    Route::get('/parishes/{parishId}/news/{newsId}/gallery', [NewsController::class, 'gallery'])->whereNumber('parishId')->whereNumber('newsId');
    Route::get('/parishes/{parishId}/news/{newsId}/attachments', [NewsController::class, 'attachments'])->whereNumber('parishId')->whereNumber('newsId');
    Route::get('/parishes/{parishId}/news/{newsId}/comments', [NewsController::class, 'comments'])->whereNumber('parishId')->whereNumber('newsId');

    Route::middleware(['api.auth'])->group(function (): void {
        Route::get('/me', [MeController::class, 'show']);
        Route::patch('/me', [MeController::class, 'update']);
        Route::patch('/me/email', [MeController::class, 'updateEmail']);
        Route::patch('/me/password', [MeController::class, 'updatePassword']);
        Route::post('/me/avatar', [MeController::class, 'uploadAvatar']);
        Route::delete('/me/avatar', [MeController::class, 'deleteAvatar']);
        Route::post('/me/parish-approval-code/regenerate', [MeController::class, 'regenerateParishApprovalCode']);

        Route::post('/me/devices', [DeviceController::class, 'store']);
        Route::delete('/me/devices/{deviceId}', [DeviceController::class, 'destroy'])->whereNumber('deviceId');

        Route::patch('/me/notification-preferences', [NotificationController::class, 'updatePreferences']);
        Route::get('/me/notifications', [NotificationController::class, 'index']);
        Route::post('/me/notifications/{id}/read', [NotificationController::class, 'markRead']);
    });

    Route::middleware(['api.auth', 'api.verified'])->prefix('parish-approvals')->group(function (): void {
        Route::get('/by-code/{code}', [ParishApprovalController::class, 'byCode'])
            ->middleware('throttle:api-parish-approval-lookup')
            ->where('code', '\d{9}');
        Route::post('/{userId}/approve', [ParishApprovalController::class, 'approve'])
            ->middleware('throttle:api-parish-approval-approve')
            ->whereNumber('userId');
        Route::get('/pending', [ParishApprovalController::class, 'pending']);
    });

    Route::middleware(['api.auth', 'api.verified'])->group(function (): void {
        Route::post('/parishes/{parishId}/masses/{massId}/attendance', [EngagementController::class, 'attendMass'])
            ->whereNumber('parishId')
            ->whereNumber('massId');

        Route::delete('/parishes/{parishId}/masses/{massId}/attendance', [EngagementController::class, 'cancelMassAttendance'])
            ->whereNumber('parishId')
            ->whereNumber('massId');

        Route::post('/parishes/{parishId}/news/{newsId}/comments', [EngagementController::class, 'addComment'])
            ->whereNumber('parishId')
            ->whereNumber('newsId');

        Route::delete('/parishes/{parishId}/news/{newsId}/comments/{commentId}', [EngagementController::class, 'deleteComment'])
            ->whereNumber('parishId')
            ->whereNumber('newsId')
            ->whereNumber('commentId');
    });

    Route::middleware(['api.auth', 'api.verified', 'api.parish_approved'])->prefix('office')->group(function (): void {
        Route::get('/parishes/{parishId}/staff', [OfficeDirectoryController::class, 'staff'])->whereNumber('parishId');
        Route::get('/parishes/{parishId}/users', [OfficeDirectoryController::class, 'users'])->whereNumber('parishId');
        Route::get('/chats', [OfficeChatController::class, 'index']);
        Route::post('/chats', [OfficeChatController::class, 'store']);
        Route::get('/chats/{chatId}/messages', [OfficeChatController::class, 'messages'])->whereNumber('chatId');
        Route::post('/chats/{chatId}/messages', [OfficeChatController::class, 'storeMessage'])->whereNumber('chatId');
        Route::post('/chats/{chatId}/attachments', [OfficeChatController::class, 'storeAttachments'])->whereNumber('chatId');
        Route::get('/chats/{chatId}/attachments/{attachmentId}', [OfficeAttachmentController::class, 'show'])
            ->whereNumber('chatId')
            ->whereNumber('attachmentId')
            ->name('api.v1.office.attachments.show');
    });
});
