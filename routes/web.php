<?php

use App\Http\Controllers\Admin\NewsPostInlineMediaController;
use App\Http\Controllers\Landing\HomeController;
use App\Http\Controllers\Landing\MailingWaitlistController;
use App\Http\Controllers\Office\OfficeAttachmentDownloadController;
use Illuminate\Support\Facades\Route;

Route::name('landing.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/regulamin', [HomeController::class, 'terms'])->name('terms');
    Route::get('/polityka-prywatnosci', [HomeController::class, 'privacy'])->name('privacy');
    Route::get('/kontakt', [HomeController::class, 'contact'])->name('contact');
    Route::post('/kontakt', [HomeController::class, 'sendContact'])
        ->middleware('throttle:5,1')
        ->name('contact.send');
    Route::get('/mailing/confirm/{token}', [MailingWaitlistController::class, 'confirm'])->name('mailing.confirm');
    Route::get('/mailing/unsubscribe/{token}', [MailingWaitlistController::class, 'unsubscribe'])->name('mailing.unsubscribe');
});

Route::name('admin.')->prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::post('/news-posts/{newsPost}/inline-image', NewsPostInlineMediaController::class)
        ->name('news-posts.inline-image');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/office/attachments/{media}', OfficeAttachmentDownloadController::class)
        ->whereNumber('media')
        ->name('office.attachments.download');
});

require __DIR__.'/auth.php';
