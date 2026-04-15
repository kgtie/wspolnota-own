<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\AnnouncementSet;
use App\Models\MailingMail;
use App\Models\Mass;
use App\Models\NewsPost;
use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\Parish;
use App\Models\PushDelivery;
use App\Models\User;
use App\Models\UserDevice;
use App\Settings\FcmSettings;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Przekrojowy pulpit kondycji całej platformy.
 *
 * Metryki są policzone globalnie, bez tenantowych ograniczeń, i mają pomagać w
 * szybkim wykrywaniu problemów z treściami, kolejką, FCM, mediami i ruchem.
 */
class SystemHealth extends Page
{
    protected static ?string $title = 'Globalne metryki';

    protected static ?string $navigationLabel = 'Globalne metryki';

    protected static string | \BackedEnum | null $navigationIcon = null;

    protected static string | \UnitEnum | null $navigationGroup = 'System i diagnostyka';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.superadmin.pages.system-health';

    protected ?string $pollingInterval = '30s';

    protected ?string $subheading = 'Przekrojowa kondycja całej platformy, bez ograniczeń tenantowych.';

    public static function getNavigationBadge(): ?string
    {
        if (! Schema::hasTable('failed_jobs')) {
            return null;
        }

        $failedJobs = DB::table('failed_jobs')->count();

        return $failedJobs > 0 ? (string) $failedJobs : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() !== null ? 'warning' : 'success';
    }

    public function getOverviewCardsProperty(): array
    {
        $parishesCount = Parish::query()->count();
        $activeParishes = Parish::query()->where('is_active', true)->count();
        $expiringParishes = Parish::query()
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();

        $usersCount = User::withTrashed()->count();
        $superAdminsCount = User::withTrashed()->where('role', 2)->count();
        $adminsCount = User::withTrashed()->where('role', 1)->count();
        $unverifiedUsers = User::withTrashed()->where('is_user_verified', false)->count();

        $massesUpcoming = Mass::query()->where('celebration_at', '>=', now())->count();
        $publishedNews = NewsPost::query()->where('status', 'published')->count();
        $publishedAnnouncements = AnnouncementSet::query()->where('status', 'published')->count();

        $officeOpen = OfficeConversation::query()->where('status', OfficeConversation::STATUS_OPEN)->count();
        $officeUnread = OfficeMessage::query()
            ->join('office_conversations', 'office_conversations.id', '=', 'office_messages.office_conversation_id')
            ->whereNull('office_messages.read_by_priest_at')
            ->whereColumn('office_messages.sender_user_id', '!=', 'office_conversations.priest_user_id')
            ->count();

        $mailingCount = MailingMail::withTrashed()->count();
        $mailingConfirmed = MailingMail::withTrashed()->whereNotNull('confirmed_at')->count();
        $pushableDevices = $this->pushableDevicesCount();
        $savedPushPreferences = $this->usersWithPushPreferencesCount();
        $missingPushPreferences = $this->usersWithoutPushPreferencesCount();
        $notDeterminedDevices = $this->notDeterminedDevicesCount();
        $pushSent24h = $this->pushDeliveriesByStatus(PushDelivery::STATUS_SENT, 24);
        $pushFailed24h = $this->pushDeliveriesByStatus(PushDelivery::STATUS_FAILED, 24);

        [$mediaFiles, $mediaSize] = $this->mediaTotals();

        return [
            [
                'label' => 'Parafie',
                'value' => number_format($parishesCount, 0, ',', ' '),
                'hint' => "Aktywne: {$activeParishes} · wygasają w ciągu 30 dni: {$expiringParishes}",
                'color' => $expiringParishes > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Użytkownicy',
                'value' => number_format($usersCount, 0, ',', ' '),
                'hint' => "Superadministratorzy: {$superAdminsCount} · administratorzy: {$adminsCount} · niezatwierdzeni: {$unverifiedUsers}",
                'color' => $unverifiedUsers > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Liturgia i treści',
                'value' => number_format($massesUpcoming, 0, ',', ' '),
                'hint' => "Nadchodzące msze · opublikowane aktualności: {$publishedNews} · opublikowane ogłoszenia: {$publishedAnnouncements}",
                'color' => 'info',
            ],
            [
                'label' => 'Kancelaria online',
                'value' => number_format($officeOpen, 0, ',', ' '),
                'hint' => "Otwarte konwersacje · nieprzeczytane po stronie admina: {$officeUnread}",
                'color' => $officeUnread > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Mailing',
                'value' => number_format($mailingCount, 0, ',', ' '),
                'hint' => "Subskrybenci łącznie · potwierdzeni: {$mailingConfirmed}",
                'color' => 'primary',
            ],
            [
                'label' => 'Push / FCM',
                'value' => number_format($pushableDevices, 0, ',', ' '),
                'hint' => "Pushable · zgody zapisane: {$savedPushPreferences} · brak zgod: {$missingPushPreferences} · not_determined: {$notDeterminedDevices} · sent 24h: {$pushSent24h} · failed 24h: {$pushFailed24h}",
                'color' => $pushFailed24h > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Media (pliki)',
                'value' => number_format($mediaFiles, 0, ',', ' '),
                'hint' => 'Łączny rozmiar: '.$this->formatBytes($mediaSize),
                'color' => $mediaFiles > 0 ? 'gray' : 'warning',
            ],
        ];
    }

    public function getInfrastructureCardsProperty(): array
    {
        $dbHealthy = $this->isDatabaseHealthy();
        $settingsCount = $this->tableCount('settings');
        $activityCount24h = $this->countActivityLogs(24);
        $activityCount1h = $this->countActivityLogs(1);
        $jobsCount = $this->tableCount(config('queue.connections.database.table', 'jobs'));
        $failedJobsCount = $this->tableCount(config('queue.failed.table', 'failed_jobs'));
        $fcmSettings = app(FcmSettings::class);
        $deadTokens = $this->deadTokensCount();

        return [
            [
                'label' => 'Baza danych',
                'value' => $dbHealthy ? 'OK' : 'BŁĄD',
                'hint' => 'Połączenie i podstawowe zapytania',
                'color' => $dbHealthy ? 'success' : 'danger',
            ],
            [
                'label' => 'Settings',
                'value' => number_format($settingsCount, 0, ',', ' '),
                'hint' => 'Wpisy konfiguracyjne globalne',
                'color' => $settingsCount > 0 ? 'primary' : 'warning',
            ],
            [
                'label' => 'Logi aktywności 24h',
                'value' => number_format($activityCount24h, 0, ',', ' '),
                'hint' => 'Ostatnia godzina: '.number_format($activityCount1h, 0, ',', ' '),
                'color' => $activityCount24h > 0 ? 'info' : 'gray',
            ],
            [
                'label' => 'Queue jobs',
                'value' => number_format($jobsCount, 0, ',', ' '),
                'hint' => 'Oczekujące zadania',
                'color' => $jobsCount > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Queue failed',
                'value' => number_format($failedJobsCount, 0, ',', ' '),
                'hint' => 'Nieudane zadania',
                'color' => $failedJobsCount > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'FCM',
                'value' => $fcmSettings->enabled ? 'ON' : 'OFF',
                'hint' => 'project_id: '.($fcmSettings->resolvedProjectId() !== '' ? $fcmSettings->resolvedProjectId() : 'brak').' · martwe tokeny: '.$deadTokens,
                'color' => ! $fcmSettings->enabled ? 'warning' : ($deadTokens > 0 ? 'warning' : 'success'),
            ],
        ];
    }

    public function getSystemSnapshotProperty(): array
    {
        $fcmSettings = app(FcmSettings::class);

        return [
            ['label' => 'Środowisko', 'value' => (string) config('app.env')],
            ['label' => 'Debug', 'value' => config('app.debug') ? 'true' : 'false'],
            ['label' => 'PHP', 'value' => PHP_VERSION],
            ['label' => 'Laravel', 'value' => app()->version()],
            ['label' => 'DB connection', 'value' => (string) config('database.default')],
            ['label' => 'Cache driver', 'value' => (string) config('cache.default')],
            ['label' => 'Queue default', 'value' => (string) config('queue.default')],
            ['label' => 'Settings cache', 'value' => config('settings.cache.enabled') ? 'enabled' : 'disabled'],
            ['label' => 'FCM enabled', 'value' => $fcmSettings->enabled ? 'true' : 'false'],
            ['label' => 'FCM project_id', 'value' => $fcmSettings->resolvedProjectId() !== '' ? $fcmSettings->resolvedProjectId() : 'brak'],
        ];
    }

    public function getPushCardsProperty(): array
    {
        $allDevices = $this->userDevicesCount();
        $pushableDevices = $this->pushableDevicesCount();
        $disabledDevices = $this->disabledDevicesCount();
        $deadTokens = $this->deadTokensCount();
        $savedPushPreferences = $this->usersWithPushPreferencesCount();
        $missingPushPreferences = $this->usersWithoutPushPreferencesCount();
        $notDeterminedDevices = $this->notDeterminedDevicesCount();
        $deniedDevices = $this->deniedDevicesCount();
        $sent24h = $this->pushDeliveriesByStatus(PushDelivery::STATUS_SENT, 24);
        $failed24h = $this->pushDeliveriesByStatus(PushDelivery::STATUS_FAILED, 24);
        $queued24h = $this->pushDeliveriesByStatus(PushDelivery::STATUS_QUEUED, 24);
        $successRate = $sent24h + $failed24h > 0
            ? round(($sent24h / max(1, $sent24h + $failed24h)) * 100, 1)
            : null;

        return [
            [
                'label' => 'Urzadzenia',
                'value' => number_format($allDevices, 0, ',', ' '),
                'hint' => "Pushable: {$pushableDevices} · disabled: {$disabledDevices} · denied: {$deniedDevices} · not_determined: {$notDeterminedDevices}",
                'color' => $pushableDevices > 0 ? 'info' : 'warning',
            ],
            [
                'label' => 'Zgody backendowe',
                'value' => number_format($savedPushPreferences, 0, ',', ' '),
                'hint' => "Zapisane preferencje · brak rekordu zgod: {$missingPushPreferences}",
                'color' => $missingPushPreferences > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Dostarczenia 24h',
                'value' => number_format($sent24h, 0, ',', ' '),
                'hint' => "Sent · failed: {$failed24h} · queued: {$queued24h}",
                'color' => $failed24h > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Skutecznosc 24h',
                'value' => $successRate === null ? 'brak' : number_format($successRate, 1, ',', ' ').'%',
                'hint' => 'Liczona jako sent / (sent + failed)',
                'color' => $successRate === null ? 'gray' : ($successRate >= 98 ? 'success' : ($successRate >= 90 ? 'warning' : 'danger')),
            ],
            [
                'label' => 'Martwe tokeny',
                'value' => number_format($deadTokens, 0, ',', ' '),
                'hint' => 'Disabled albo z bledem UNREGISTERED / INVALID_ARGUMENT',
                'color' => $deadTokens > 0 ? 'danger' : 'success',
            ],
        ];
    }

    public function getActivityBreakdownProperty(): array
    {
        $table = config('activitylog.table_name', 'activity_log');

        if (! $this->tableExists($table)) {
            return [];
        }

        return DB::table($table)
            ->selectRaw("COALESCE(NULLIF(event, ''), 'no_event') as event_name, COUNT(*) as total")
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('event_name')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn ($row): array => [
                'event' => (string) $row->event_name,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    public function getMediaBreakdownProperty(): array
    {
        if (! $this->tableExists('media')) {
            return [];
        }

        return DB::table('media')
            ->selectRaw('disk, COUNT(*) as files_count, COALESCE(SUM(size), 0) as total_size')
            ->groupBy('disk')
            ->orderByDesc('files_count')
            ->get()
            ->map(fn ($row): array => [
                'disk' => (string) $row->disk,
                'files_count' => (int) $row->files_count,
                'total_size' => (int) $row->total_size,
                'total_size_human' => $this->formatBytes((int) $row->total_size),
            ])
            ->all();
    }

    public function getQueueBreakdownProperty(): array
    {
        $jobsTable = config('queue.connections.database.table', 'jobs');
        $failedTable = config('queue.failed.table', 'failed_jobs');

        $rows = [];

        if ($this->tableExists($jobsTable)) {
            $pendingByQueue = DB::table($jobsTable)
                ->selectRaw("COALESCE(NULLIF(queue, ''), 'default') as queue_name, COUNT(*) as total")
                ->groupBy('queue_name')
                ->orderByDesc('total')
                ->get();

            foreach ($pendingByQueue as $row) {
                $queueName = (string) $row->queue_name;
                $rows[$queueName] = [
                    'queue' => $queueName,
                    'pending' => (int) $row->total,
                    'failed' => 0,
                ];
            }
        }

        if ($this->tableExists($failedTable)) {
            $failedByQueue = DB::table($failedTable)
                ->selectRaw("COALESCE(NULLIF(queue, ''), 'default') as queue_name, COUNT(*) as total")
                ->groupBy('queue_name')
                ->orderByDesc('total')
                ->get();

            foreach ($failedByQueue as $row) {
                $queueName = (string) $row->queue_name;

                if (! isset($rows[$queueName])) {
                    $rows[$queueName] = [
                        'queue' => $queueName,
                        'pending' => 0,
                        'failed' => 0,
                    ];
                }

                $rows[$queueName]['failed'] = (int) $row->total;
            }
        }

        return collect($rows)
            ->sortByDesc(fn (array $row): int => $row['failed'] * 100000 + $row['pending'])
            ->values()
            ->all();
    }

    public function getPushTypeBreakdownProperty(): array
    {
        if (! $this->tableExists('push_deliveries')) {
            return [];
        }

        return PushDelivery::query()
            ->selectRaw("COALESCE(NULLIF(type, ''), 'unknown') as push_type")
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sent_count", [PushDelivery::STATUS_SENT])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_count", [PushDelivery::STATUS_FAILED])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as queued_count", [PushDelivery::STATUS_QUEUED])
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('push_type')
            ->orderByRaw(
                "SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) + SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) + SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) DESC",
                [
                    PushDelivery::STATUS_SENT,
                    PushDelivery::STATUS_FAILED,
                    PushDelivery::STATUS_QUEUED,
                ],
            )
            ->get()
            ->map(fn (PushDelivery $row): array => [
                'type' => (string) $row->push_type,
                'sent' => (int) $row->sent_count,
                'failed' => (int) $row->failed_count,
                'queued' => (int) $row->queued_count,
                'total' => (int) $row->sent_count + (int) $row->failed_count + (int) $row->queued_count,
            ])
            ->all();
    }

    public function getPushPlatformBreakdownProperty(): array
    {
        if (! $this->tableExists('user_devices')) {
            return [];
        }

        return UserDevice::query()
            ->selectRaw("COALESCE(NULLIF(platform, ''), 'unknown') as device_platform")
            ->selectRaw('COUNT(*) as total_devices')
            ->selectRaw("SUM(CASE WHEN disabled_at IS NOT NULL THEN 1 ELSE 0 END) as disabled_devices")
            ->selectRaw("SUM(CASE WHEN disabled_at IS NULL AND provider = 'fcm' AND push_token IS NOT NULL AND permission_status IN ('authorized', 'provisional') THEN 1 ELSE 0 END) as pushable_devices")
            ->selectRaw("SUM(CASE WHEN permission_status = 'denied' THEN 1 ELSE 0 END) as denied_devices")
            ->groupBy('device_platform')
            ->orderByDesc('total_devices')
            ->get()
            ->map(fn (UserDevice $row): array => [
                'platform' => (string) $row->device_platform,
                'total' => (int) $row->total_devices,
                'pushable' => (int) $row->pushable_devices,
                'disabled' => (int) $row->disabled_devices,
                'denied' => (int) $row->denied_devices,
            ])
            ->all();
    }

    public function getConversationHotspotsProperty(): array
    {
        return OfficeConversation::query()
            ->join('parishes', 'parishes.id', '=', 'office_conversations.parish_id')
            ->leftJoin('office_messages', 'office_messages.office_conversation_id', '=', 'office_conversations.id')
            ->selectRaw('parishes.id as parish_id')
            ->selectRaw('parishes.name as parish_name')
            ->selectRaw('COUNT(DISTINCT office_conversations.id) as conversations_count')
            ->selectRaw("COUNT(DISTINCT CASE WHEN office_conversations.status = 'open' THEN office_conversations.id END) as open_count")
            ->selectRaw("SUM(CASE WHEN office_messages.read_by_priest_at IS NULL AND office_messages.sender_user_id != office_conversations.priest_user_id THEN 1 ELSE 0 END) as unread_for_priest")
            ->groupBy('parishes.id', 'parishes.name')
            ->orderByDesc('unread_for_priest')
            ->orderByDesc('open_count')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'parish_name' => (string) $row->parish_name,
                'conversations_count' => (int) $row->conversations_count,
                'open_count' => (int) $row->open_count,
                'unread_for_priest' => (int) $row->unread_for_priest,
            ])
            ->all();
    }

    protected function mediaTotals(): array
    {
        if (! $this->tableExists('media')) {
            return [0, 0];
        }

        $totals = DB::table('media')
            ->selectRaw('COUNT(*) as files_count, COALESCE(SUM(size), 0) as total_size')
            ->first();

        if (! $totals) {
            return [0, 0];
        }

        return [(int) $totals->files_count, (int) $totals->total_size];
    }

    protected function userDevicesCount(): int
    {
        if (! $this->tableExists('user_devices')) {
            return 0;
        }

        return (int) UserDevice::query()->count();
    }

    protected function pushableDevicesCount(): int
    {
        if (! $this->tableExists('user_devices')) {
            return 0;
        }

        return (int) UserDevice::query()->pushable()->count();
    }

    protected function disabledDevicesCount(): int
    {
        if (! $this->tableExists('user_devices')) {
            return 0;
        }

        return (int) UserDevice::query()->whereNotNull('disabled_at')->count();
    }

    protected function deniedDevicesCount(): int
    {
        if (! $this->tableExists('user_devices')) {
            return 0;
        }

        return (int) UserDevice::query()->where('permission_status', 'denied')->count();
    }

    protected function notDeterminedDevicesCount(): int
    {
        if (! $this->tableExists('user_devices')) {
            return 0;
        }

        return (int) UserDevice::query()
            ->where(function ($query): void {
                $query
                    ->where('permission_status', 'not_determined')
                    ->orWhereNull('permission_status');
            })
            ->count();
    }

    protected function usersWithPushPreferencesCount(): int
    {
        if (! $this->tableExists('user_notification_preferences')) {
            return 0;
        }

        return (int) User::query()->whereHas('notificationPreference')->count();
    }

    protected function usersWithoutPushPreferencesCount(): int
    {
        if (! $this->tableExists('user_notification_preferences')) {
            return 0;
        }

        return (int) User::query()->whereDoesntHave('notificationPreference')->count();
    }

    protected function deadTokensCount(): int
    {
        if (! $this->tableExists('user_devices')) {
            return 0;
        }

        return (int) UserDevice::query()
            ->deadToken()
            ->count();
    }

    protected function pushDeliveriesByStatus(string $status, int $hours): int
    {
        if (! $this->tableExists('push_deliveries')) {
            return 0;
        }

        return (int) PushDelivery::query()
            ->where('status', $status)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }

    protected function isDatabaseHealthy(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function tableCount(string $table): int
    {
        if (! $this->tableExists($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    protected function countActivityLogs(int $hours): int
    {
        $table = config('activitylog.table_name', 'activity_log');

        if (! $this->tableExists($table)) {
            return 0;
        }

        return (int) DB::table($table)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }

    protected function tableExists(string $table): bool
    {
        if ($table === '') {
            return false;
        }

        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 2, ',', ' ').' '.$units[$power];
    }
}
