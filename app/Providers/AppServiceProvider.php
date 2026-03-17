<?php

namespace App\Providers;

use App\Events\AnnouncementPackagePublished;
use App\Events\NewsPublished;
use App\Events\OfficeMessageReceived;
use App\Events\ParishApprovalStatusChanged;
use App\Contracts\PushSender;
use App\Listeners\DispatchAnnouncementPackagePublishedNotifications;
use App\Listeners\DispatchNewsPublishedNotifications;
use App\Listeners\DispatchOfficeMessageReceivedNotifications;
use App\Listeners\DispatchParishApprovalStatusChangedNotifications;
use App\Listeners\QueuePushFromDatabaseNotification;
use App\Models\AnnouncementSet;
use App\Support\Push\FcmPushSender;
use App\Observers\AnnouncementSetObserver;
use Carbon\Carbon;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PushSender::class, FcmPushSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('pl');

        AnnouncementSet::observe(AnnouncementSetObserver::class);

        Event::listen(NewsPublished::class, DispatchNewsPublishedNotifications::class);
        Event::listen(AnnouncementPackagePublished::class, DispatchAnnouncementPackagePublishedNotifications::class);
        Event::listen(OfficeMessageReceived::class, DispatchOfficeMessageReceivedNotifications::class);
        Event::listen(ParishApprovalStatusChanged::class, DispatchParishApprovalStatusChangedNotifications::class);
        Event::listen(NotificationSent::class, QueuePushFromDatabaseNotification::class);
    }
}
