<?php

use App\Http\Controllers\Admin\NewsPostInlineMediaController;
// Importowanie kontrolerów LANDING
use App\Http\Controllers\App\AnnouncementsController as AppAnnouncementsController;
use App\Http\Controllers\App\AppController;
// Importowanie kontrolerów APP
use App\Http\Controllers\App\HomeController as AppHomeController;
use App\Http\Controllers\App\MassCalendarController as AppMassCalendarController;
use App\Http\Controllers\App\OfficeController as AppOfficeController;
use App\Http\Controllers\Landing\HomeController;
use App\Http\Controllers\Landing\MailingWaitlistController;
// Importowanie kontrolerów ADMIN
use Illuminate\Support\Facades\Route;

/**
 * Routing dla LANDING
 */
Route::name('landing.')->group(function () {
    // Strona główna
    Route::get('/', [HomeController::class, 'index'])->name('home');
    // Potwierdzenie dopisania do listy mailingowej osób oczekujących na uruchomienie usługi
    Route::get('/mailing/confirm/{token}', [MailingWaitlistController::class, 'confirm'])->name('mailing.confirm');
    Route::get('/mailing/unsubscribe/{token}', [MailingWaitlistController::class, 'unsubscribe'])->name('mailing.unsubscribe');
});

/**
 * Routing dla APP
 */
Route::name('app.')->prefix('app')->group(function () {
    // Rozdzielacz tras w razie zaistnienia różnych scenariuszy (czy pierwszy raz się loguje -> onboarding; czy ma parafię -> przekierowanie; itp.)
    Route::get('/', [AppController::class, 'app_route']);
    Route::prefix('{parish}')->middleware(['parish.active'])->group(function () {
        // Strona główna aplikacji dla danej parafii (index lub home). DOSTĘP: każdy.
        Route::get('/', [AppHomeController::class, 'index'])->name('home');
        // Kalendarz mszy. DOSTĘP: każdy.
        Route::get('/mass-calendar', [AppMassCalendarController::class, 'index'])->name('mass_calendar');
        // Ogłoszenia. DOSTĘP: każdy.
        Route::get('/announcements', [AppAnnouncementsController::class, 'index'])->name('announcements');
        // Biuro parafialne, a więc "kancelaria parafialna online". DOSTĘP: zalogowani oraz zweryfikowani co do adresu email.
        Route::get('/office', [AppOfficeController::class, 'index'])->middleware(['auth', 'verified'])->name('office');
    });
});

/**
 * Routing dla ADMIN
 */
Route::name('admin.')->prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::post('/news-posts/{newsPost}/inline-image', NewsPostInlineMediaController::class)
        ->name('news-posts.inline-image');
});

/**
 * Dodatkowy routing
 */
require __DIR__.'/auth.php';
