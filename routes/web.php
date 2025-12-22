<?php


use Illuminate\Support\Facades\Route;

// Importowanie kontrolerów LANDING
use App\Http\Controllers\Landing\HomeController;
use App\Http\Controllers\Landing\MailingWaitlistController;

// Importowanie kontrolerów APP
use App\Http\Controllers\App\AppController;
use App\Http\Controllers\App\HomeController as AppHomeController;
use App\Http\Controllers\App\AnnouncementsController as AppAnnouncementsController;
use App\Http\Controllers\App\MassCalendarController as AppMassCalendarController;
use App\Http\Controllers\App\OfficeController as AppOfficeController;

// Importowanie kontrolerów ADMIN
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UsersController as AdminUsersController;

// Importowanie kontrolerów SUPERADMIN
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\UsersController as SuperadminUsersController;


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
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', AdminUsersController::class);
});

/**
 * Routing dla SUPERADMIN
 */
Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', [SuperadminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users/trash', [SuperadminUsersController::class, 'trash'])->name('users.trash');
    Route::put('/users/{id}/restore', [SuperadminUsersController::class, 'restore'])->name('users.restore');
    Route::delete('/users/{id}/force-delete', [SuperadminUsersController::class, 'forceDelete'])->name('users.force_delete');
    Route::resource('users', SuperadminUsersController::class);
});

require __DIR__.'/auth.php';
