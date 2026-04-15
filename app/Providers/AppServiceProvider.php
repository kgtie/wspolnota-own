<?php

namespace App\Providers;

use App\Events\OfficeMessageReceived;
use App\Events\ParishApprovalStatusChanged;
use App\Contracts\PushSender;
use App\Listeners\DispatchOfficeMessageReceivedNotifications;
use App\Listeners\DispatchParishApprovalStatusChangedNotifications;
use App\Listeners\QueuePushFromDatabaseNotification;
use App\Models\AnnouncementSet;
use App\Support\Push\FcmPushSender;
use App\Observers\AnnouncementSetObserver;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Rejestruje singletony i usługi infrastrukturalne współdzielone w aplikacji.
     */
    public function register(): void
    {
        $this->app->singleton(PushSender::class, FcmPushSender::class);
    }

    /**
     * Uruchamia listenery domenowe oraz named rate limiters dla API/web.
     */
    public function boot(): void
    {
        Carbon::setLocale('pl');
        $this->configureApiRateLimiters();

        AnnouncementSet::observe(AnnouncementSetObserver::class);

        Event::listen(OfficeMessageReceived::class, DispatchOfficeMessageReceivedNotifications::class);
        Event::listen(ParishApprovalStatusChanged::class, DispatchParishApprovalStatusChangedNotifications::class);
        Event::listen(NotificationSent::class, QueuePushFromDatabaseNotification::class);
    }

    /**
     * Named limiters są używane wyłącznie przez krytyczne endpointy API.
     * Klucze łączą IP i kontekst żądania, aby ograniczać brute force bez
     * blokowania legalnego ruchu całej aplikacji jednym globalnym limitem.
     */
    private function configureApiRateLimiters(): void
    {
        RateLimiter::for('api-auth-login', function (Request $request): array {
            $login = mb_strtolower(trim((string) $request->input('login', '')));

            return [
                Limit::perMinute(5)->by('api-auth-login:credential:'.sha1($login.'|'.$request->ip())),
                Limit::perMinute(20)->by('api-auth-login:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('api-auth-register', function (Request $request): array {
            $email = mb_strtolower(trim((string) $request->input('email', '')));

            return [
                Limit::perMinute(3)->by('api-auth-register:email:'.sha1($email.'|'.$request->ip())),
                Limit::perMinute(10)->by('api-auth-register:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('api-auth-refresh', function (Request $request): array {
            $deviceId = (string) data_get($request->input('device'), 'device_id', '');

            return [
                Limit::perMinute(30)->by('api-auth-refresh:device:'.sha1($deviceId.'|'.$request->ip())),
                Limit::perMinute(60)->by('api-auth-refresh:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('api-auth-forgot-password', function (Request $request): array {
            $email = mb_strtolower(trim((string) $request->input('email', '')));

            return [
                Limit::perMinute(3)->by('api-auth-forgot-password:email:'.sha1($email.'|'.$request->ip())),
                Limit::perMinute(10)->by('api-auth-forgot-password:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('api-auth-reset-password', fn (Request $request): array => [
            Limit::perMinute(5)->by('api-auth-reset-password:ip:'.$request->ip()),
        ]);

        RateLimiter::for('api-auth-verification-resend', function (Request $request): array {
            $userId = (string) optional($request->user())->getAuthIdentifier();

            return [
                Limit::perMinute(3)->by('api-auth-verification-resend:user:'.$userId.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('api-parish-approval-lookup', function (Request $request): array {
            $userId = (string) optional($request->user())->getAuthIdentifier();

            return [
                Limit::perMinute(20)->by('api-parish-approval-lookup:user:'.$userId.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('api-parish-approval-approve', function (Request $request): array {
            $userId = (string) optional($request->user())->getAuthIdentifier();

            return [
                Limit::perMinute(15)->by('api-parish-approval-approve:user:'.$userId.'|'.$request->ip()),
            ];
        });
    }
}
