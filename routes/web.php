<?php

use App\Http\Controllers\Admin\NewsPostInlineMediaController;
use App\Http\Controllers\Admin\CommunicationCampaignInlineMediaController;
use App\Http\Controllers\Landing\HomeController;
use App\Http\Controllers\Landing\MailingWaitlistController;
use App\Http\Controllers\Office\OfficeAttachmentDownloadController;
use App\Http\Controllers\Parish\AnnouncementPdfController as ParishAnnouncementPdfController;
use App\Http\Controllers\Parish\EmailVerificationController as ParishEmailVerificationController;
use App\Http\Controllers\Parish\HomeController as ParishController;
use App\Http\Controllers\Parish\InterestController as ParishInterestController;
use Illuminate\Support\Facades\Route;

Route::domain(env('APP_URL_APP'))->group(function () {
    Route::name('landing.')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/regulamin', [HomeController::class, 'terms'])->name('terms');
        Route::get('/polityka-prywatnosci', [HomeController::class, 'privacy'])->name('privacy');
        Route::get('/kontakt', [HomeController::class, 'contact'])->name('contact');
        Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');
        Route::get('/robots.txt', [HomeController::class, 'robots'])->name('robots');
        Route::post('/kontakt', [HomeController::class, 'sendContact'])
            ->middleware('throttle:5,1')
            ->name('contact.send');
        Route::get('/mailing/confirm/{token}', [MailingWaitlistController::class, 'confirm'])->name('mailing.confirm');
        Route::get('/mailing/unsubscribe/{token}', [MailingWaitlistController::class, 'unsubscribe'])->name('mailing.unsubscribe');
    });

    Route::name('admin.')->prefix('admin')->middleware(['auth', 'admin'])->group(function () {
        Route::post('/news-posts/{newsPost}/inline-image', NewsPostInlineMediaController::class)
            ->name('news-posts.inline-image');
        Route::post('/communication-campaigns/{campaign}/inline-image', CommunicationCampaignInlineMediaController::class)
            ->name('communication-campaigns.inline-image');
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/office/attachments/{media}', OfficeAttachmentDownloadController::class)
            ->whereNumber('media')
            ->name('office.attachments.download');
    });

    require __DIR__.'/auth.php';
});

Route::domain(env('APP_URL_EU'))->group(function () {
    Route::get('/', function () {
        return redirect()->route('landing.home');
    });
});
Route::domain('{subdomain}.'.parse_url(env('APP_URL_EU'), PHP_URL_HOST))->group(function ($subdomain) {
    Route::get('/', [ParishController::class, 'index'])->name('parish.home');
    Route::get('/potwierdzenie-email/{id}/{hash}', ParishEmailVerificationController::class)
        ->whereNumber('id')
        ->name('parish.verification.verify');
    Route::get('/ogloszenia.pdf', ParishAnnouncementPdfController::class)
        ->name('parish.announcements.pdf');
    Route::post('/interest', [ParishInterestController::class, 'store'])
        ->middleware('throttle:3,10')
        ->name('parish.interest.store');
});
