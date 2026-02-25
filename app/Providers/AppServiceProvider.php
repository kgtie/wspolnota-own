<?php

namespace App\Providers;

use App\Models\AnnouncementSet;
use App\Observers\AnnouncementSetObserver;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale("pl");

        AnnouncementSet::observe(AnnouncementSetObserver::class);
    }
}
