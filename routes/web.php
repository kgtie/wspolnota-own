<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Landing\HomeController;
use App\Http\Controllers\Landing\MailingWaitlistController;

/**
 * Routing dla LANDING
 */
Route::name("landing.")->group(function () {
    // Strona główna
    Route::get("/", [HomeController::class, "index"])->name("home");
    // Potwierdzenie dopisania do listy mailingowej osób oczekujących na uruchomienie usługi
    Route::get('/mailing/confirm/{token}', [MailingWaitlistController::class, 'confirm'])->name('mailing.confirm');
    Route::get('/mailing/unsubscribe/{token}', [MailingWaitlistController::class, 'unsubscribe'])->name('mailing.unsubscribe');
});

/**
 * Routing dla ADMIN
 */
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        echo 'Ok, jesteś adminem.';
    })->name('dashboard');
});

/**
 * Routing dla SUPERADMIN
 */
Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', function () {
        echo 'Ok, jesteś superadminem.';
    })->name('dashboard');
});

require __DIR__.'/auth.php';
