<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\SuperAdmin\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\SuperAdmin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Filament\SuperAdmin\Resources\Masses\MassResource;
use App\Filament\SuperAdmin\Resources\Media\MediaResource;
use App\Filament\SuperAdmin\Resources\Parishes\ParishResource;
use App\Filament\SuperAdmin\Resources\PushDeliveries\PushDeliveryResource;
use App\Filament\SuperAdmin\Resources\Settings\SettingResource;
use App\Filament\SuperAdmin\Resources\UserDevices\UserDeviceResource;
use App\Filament\SuperAdmin\Resources\Users\UserResource;
use App\Filament\SuperAdmin\Resources\NewsPosts\NewsPostResource;
use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\MailingMail;
use App\Models\NewsPost;
use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\Parish;
use App\Models\PushDelivery;
use App\Models\User;
use App\Models\UserDevice;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Startowy pulpit operacyjny superadministratora.
 *
 * Dashboard zbiera w jednym miejscu KPI, alerty, trendy i skroty do
 * najwazniejszych modulow, zeby /superadmin pelnil role control tower.
 */
class Dashboard extends BaseDashboard
{
    protected static bool $isDiscovered = false;

    protected static ?string $title = 'Centrum dowodzenia';

    protected static ?string $navigationLabel = 'Start';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -10;

    protected ?string $subheading = 'Globalny pulpit operacyjny superadministratora: platforma, tresci, push, kolejki i kancelaria online.';

    protected string $view = 'filament.superadmin.pages.dashboard';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected Width | string | null $maxContentWidth = 'full';

    public string $trendRange = '24h';

    /**
     * @var array<int,string>
     */
    public array $hiddenTrendSeries = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dispatch')
                ->label('Centrum dispatchu')
                ->icon('heroicon-o-bolt')
                ->url(NotificationDispatchCenter::getUrl()),
            Action::make('communication')
                ->label('Centrum komunikacji')
                ->icon('heroicon-o-envelope-open')
                ->url(CommunicationCenter::getUrl()),
            Action::make('system')
                ->label('Globalne metryki')
                ->icon('heroicon-o-chart-bar-square')
                ->url(SystemHealth::getUrl()),
            Action::make('failed_jobs')
                ->label('Failed Jobs')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->url(FailedJobsCenter::getUrl()),
        ];
    }

    public function getHeroCardsProperty(): array
    {
        $failedJobs = $this->failedJobsCount();
        $failedPush24h = PushDelivery::query()
            ->where('status', PushDelivery::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $openConversations = OfficeConversation::query()
            ->where('status', OfficeConversation::STATUS_OPEN)
            ->count();
        $unreadOffice = OfficeMessage::query()
            ->join('office_conversations', 'office_conversations.id', '=', 'office_messages.office_conversation_id')
            ->whereNull('office_messages.read_by_priest_at')
            ->whereColumn('office_messages.sender_user_id', '!=', 'office_conversations.priest_user_id')
            ->count();
        $pendingUsers = User::query()->where('is_user_verified', false)->count();
        $pushableDevices = UserDevice::query()->pushable()->count();
        $publishedNews = NewsPost::query()->where('status', 'published')->count();
        $publishedAnnouncements = AnnouncementSet::query()->where('status', 'published')->count();

        return [
            [
                'label' => 'Parafie',
                'value' => number_format(Parish::query()->count(), 0, ',', ' '),
                'hint' => 'Nieaktywne: '.number_format(Parish::query()->where('is_active', false)->count(), 0, ',', ' '),
                'tone' => Parish::query()->where('is_active', false)->exists() ? 'warning' : 'success',
            ],
            [
                'label' => 'Uzytkownicy',
                'value' => number_format(User::withTrashed()->count(), 0, ',', ' '),
                'hint' => 'Oczekuje na zatwierdzenie: '.number_format($pendingUsers, 0, ',', ' '),
                'tone' => $pendingUsers > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Push i urzadzenia',
                'value' => number_format($pushableDevices, 0, ',', ' '),
                'hint' => "Push failed 24h: {$failedPush24h}",
                'tone' => $failedPush24h > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Kancelaria online',
                'value' => number_format($openConversations, 0, ',', ' '),
                'hint' => "Nieprzeczytane watki: {$unreadOffice}",
                'tone' => $unreadOffice > 0 ? 'warning' : 'info',
            ],
            [
                'label' => 'Tresci opublikowane',
                'value' => number_format($publishedNews + $publishedAnnouncements, 0, ',', ' '),
                'hint' => "News: {$publishedNews} · ogloszenia: {$publishedAnnouncements}",
                'tone' => 'primary',
            ],
            [
                'label' => 'Kolejka i awarie',
                'value' => number_format($failedJobs, 0, ',', ' '),
                'hint' => 'Pending jobs: '.number_format($this->jobsCount(), 0, ',', ' '),
                'tone' => $failedJobs > 0 ? 'danger' : 'success',
            ],
        ];
    }

    public function getAlertCardsProperty(): array
    {
        $dispatch = $this->dispatchQueueCounts();
        $deadTokens = UserDevice::query()->deadToken()->count();
        $failedPush24h = PushDelivery::query()
            ->where('status', PushDelivery::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return [
            [
                'label' => 'Failed jobs',
                'value' => $this->failedJobsCount(),
                'description' => 'Bledy kolejki wymagajace retry lub forget.',
                'url' => FailedJobsCenter::getUrl(),
                'tone' => $this->failedJobsCount() > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Dispatch zalegly',
                'value' => $dispatch['news'] + $dispatch['announcements'],
                'description' => "News: {$dispatch['news']} · ogloszenia: {$dispatch['announcements']}",
                'url' => NotificationDispatchCenter::getUrl(),
                'tone' => ($dispatch['news'] + $dispatch['announcements']) > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Push failed 24h',
                'value' => $failedPush24h,
                'description' => 'Nieudane dostarczenia push z ostatnich 24 godzin.',
                'url' => PushDeliveryResource::getUrl('index'),
                'tone' => $failedPush24h > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Martwe tokeny',
                'value' => $deadTokens,
                'description' => 'Urzadzenia z bledem UNREGISTERED / INVALID_ARGUMENT.',
                'url' => UserDeviceResource::getUrl('index'),
                'tone' => $deadTokens > 0 ? 'warning' : 'success',
            ],
        ];
    }

    public function getQuickLinksProperty(): array
    {
        return [
            ['label' => 'Parafie', 'description' => 'Pelna administracja tenantami.', 'url' => ParishResource::getUrl('index')],
            ['label' => 'Uzytkownicy', 'description' => 'Parafianie, admini, weryfikacje i avatary.', 'url' => UserResource::getUrl('index')],
            ['label' => 'Aktualnosci', 'description' => 'Publikacja i dystrybucja newsow.', 'url' => NewsPostResource::getUrl('index')],
            ['label' => 'Ogloszenia', 'description' => 'Pakiety ogloszen i ich dispatch.', 'url' => AnnouncementSetResource::getUrl('index')],
            ['label' => 'Msze i intencje', 'description' => 'Liturgia, uczestnicy i przypomnienia.', 'url' => MassResource::getUrl('index')],
            ['label' => 'Centrum komunikacji', 'description' => 'Kampanie mailowe i push.', 'url' => CommunicationCenter::getUrl()],
            ['label' => 'Konwersacje online', 'description' => 'Globalny inbox kancelarii.', 'url' => OfficeInbox::getUrl()],
            ['label' => 'FCM i push', 'description' => 'Konfiguracja i testy dostarczen.', 'url' => FcmSettingsPage::getUrl()],
            ['label' => 'Media', 'description' => 'Wszystkie pliki i kolekcje mediow.', 'url' => MediaResource::getUrl('index')],
            ['label' => 'Ustawienia aplikacji', 'description' => 'Settings i klucze konfiguracyjne.', 'url' => SettingResource::getUrl('index')],
            ['label' => 'Logi aktywnosci', 'description' => 'Audyt dzialan w systemie.', 'url' => ActivityLogResource::getUrl('index')],
            ['label' => 'Globalne metryki', 'description' => 'Rozszerzona kondycja platformy.', 'url' => SystemHealth::getUrl()],
        ];
    }

    public function setTrendRange(string $range): void
    {
        if (! in_array($range, ['24h', '7d', '30d'], true)) {
            return;
        }

        $this->trendRange = $range;
    }

    public function toggleTrendSeries(string $panel, string $series): void
    {
        $key = "{$panel}.{$series}";

        if (in_array($key, $this->hiddenTrendSeries, true)) {
            $this->hiddenTrendSeries = array_values(array_filter(
                $this->hiddenTrendSeries,
                fn (string $hidden): bool => $hidden !== $key,
            ));

            return;
        }

        $this->hiddenTrendSeries[] = $key;
    }

    public function getTrendRangeOptionsProperty(): array
    {
        return [
            '24h' => '24h',
            '7d' => '7 dni',
            '30d' => '30 dni',
        ];
    }

    public function getTrendPanelsProperty(): array
    {
        $config = $this->resolveTrendRangeConfig();
        $start = $config['start'];

        $activityRows = $this->fetchTableTimestamps(config('activitylog.table_name', 'activity_log'), 'created_at', $start);
        $usersRows = User::query()->where('created_at', '>=', $start)->pluck('created_at');
        $failedJobsRows = $this->fetchTableTimestamps(config('queue.failed.table', 'failed_jobs'), 'failed_at', $start);
        $newsRows = NewsPost::query()
            ->where('status', 'published')
            ->where(function ($query) use ($start): void {
                $query->where('published_at', '>=', $start)
                    ->orWhere(function ($inner) use ($start): void {
                        $inner->whereNull('published_at')->where('created_at', '>=', $start);
                    });
            })
            ->get()
            ->map(fn (NewsPost $post) => $post->published_at ?? $post->created_at);
        $announcementRows = AnnouncementSet::query()
            ->where('status', 'published')
            ->where(function ($query) use ($start): void {
                $query->where('published_at', '>=', $start)
                    ->orWhere(function ($inner) use ($start): void {
                        $inner->whereNull('published_at')->where('created_at', '>=', $start);
                    });
            })
            ->get()
            ->map(fn (AnnouncementSet $set) => $set->published_at ?? $set->created_at);
        $mailingSubscriberRows = MailingMail::query()
            ->where('created_at', '>=', $start)
            ->pluck('created_at');
        $pushRows = PushDelivery::query()
            ->select(['status', 'created_at', 'sent_at'])
            ->where('created_at', '>=', $start)
            ->get();
        $deviceTokenRows = UserDevice::query()
            ->where('push_token_updated_at', '>=', $start)
            ->pluck('push_token_updated_at');
        $officeConversationRows = OfficeConversation::query()
            ->where('created_at', '>=', $start)
            ->pluck('created_at');
        $officeMessageRows = OfficeMessage::query()
            ->where('created_at', '>=', $start)
            ->pluck('created_at');
        $officeClosedRows = OfficeConversation::query()
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', $start)
            ->pluck('closed_at');

        return [
            $this->buildTrendPanel(
                key: 'platform',
                title: 'Platforma',
                description: 'Aktywnosc systemowa, wzrost uzytkownikow i awarie kolejki.',
                config: $config,
                series: [
                    [
                        'key' => 'activity_logs',
                        'label' => 'Logi aktywnosci',
                        'tone' => 'primary',
                        'values' => $this->bucketizeTimestamps($activityRows, $start, $config['steps'], $config['bucket']),
                    ],
                    [
                        'key' => 'new_users',
                        'label' => 'Nowi uzytkownicy',
                        'tone' => 'info',
                        'values' => $this->bucketizeTimestamps($usersRows, $start, $config['steps'], $config['bucket']),
                    ],
                    [
                        'key' => 'failed_jobs',
                        'label' => 'Failed jobs',
                        'tone' => 'danger',
                        'values' => $this->bucketizeTimestamps($failedJobsRows, $start, $config['steps'], $config['bucket']),
                    ],
                ],
            ),
            $this->buildTrendPanel(
                key: 'communication',
                title: 'Komunikacja',
                description: 'Publikacje i wzrost bazy komunikacyjnej platformy.',
                config: $config,
                series: [
                    [
                        'key' => 'news',
                        'label' => 'Opublikowane newsy',
                        'tone' => 'success',
                        'values' => $this->bucketizeTimestamps($newsRows, $start, $config['steps'], $config['bucket']),
                    ],
                    [
                        'key' => 'announcements',
                        'label' => 'Opublikowane ogloszenia',
                        'tone' => 'warning',
                        'values' => $this->bucketizeTimestamps($announcementRows, $start, $config['steps'], $config['bucket']),
                    ],
                    [
                        'key' => 'mailing_subscribers',
                        'label' => 'Nowi subskrybenci',
                        'tone' => 'primary',
                        'values' => $this->bucketizeTimestamps($mailingSubscriberRows, $start, $config['steps'], $config['bucket']),
                    ],
                ],
            ),
            $this->buildTrendPanel(
                key: 'push',
                title: 'Push',
                description: 'Wydajnosc dostarczen, bledy i przyrost aktywnych tokenow.',
                config: $config,
                series: [
                    [
                        'key' => 'push_sent',
                        'label' => 'Push sent',
                        'tone' => 'success',
                        'values' => $this->bucketizeTimestamps(
                            $pushRows->where('status', PushDelivery::STATUS_SENT)->pluck('sent_at')->filter(),
                            $start,
                            $config['steps'],
                            $config['bucket'],
                        ),
                    ],
                    [
                        'key' => 'push_failed',
                        'label' => 'Push failed',
                        'tone' => 'danger',
                        'values' => $this->bucketizeTimestamps(
                            $pushRows->where('status', PushDelivery::STATUS_FAILED)->pluck('created_at'),
                            $start,
                            $config['steps'],
                            $config['bucket'],
                        ),
                    ],
                    [
                        'key' => 'token_updates',
                        'label' => 'Token updates',
                        'tone' => 'info',
                        'values' => $this->bucketizeTimestamps($deviceTokenRows, $start, $config['steps'], $config['bucket']),
                    ],
                ],
            ),
            $this->buildTrendPanel(
                key: 'office',
                title: 'Kancelaria',
                description: 'Naplyw nowych spraw i tempo ich domykania.',
                config: $config,
                series: [
                    [
                        'key' => 'new_conversations',
                        'label' => 'Nowe konwersacje',
                        'tone' => 'primary',
                        'values' => $this->bucketizeTimestamps($officeConversationRows, $start, $config['steps'], $config['bucket']),
                    ],
                    [
                        'key' => 'new_messages',
                        'label' => 'Nowe wiadomosci',
                        'tone' => 'info',
                        'values' => $this->bucketizeTimestamps($officeMessageRows, $start, $config['steps'], $config['bucket']),
                    ],
                    [
                        'key' => 'closed_conversations',
                        'label' => 'Zamkniete konwersacje',
                        'tone' => 'success',
                        'values' => $this->bucketizeTimestamps($officeClosedRows, $start, $config['steps'], $config['bucket']),
                    ],
                ],
            ),
        ];
    }

    public function getPendingUsersProperty(): array
    {
        return User::query()
            ->with('homeParish:id,name')
            ->where('is_user_verified', false)
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->getKey(),
                'name' => $user->full_name ?: $user->name ?: 'Brak nazwy',
                'email' => (string) $user->email,
                'parish' => (string) ($user->homeParish?->name ?? 'Brak parafii'),
                'created_at' => $user->created_at?->format('d.m.Y H:i') ?? '-',
                'url' => UserResource::getUrl('view', ['record' => $user]),
            ])
            ->all();
    }

    public function getDueDispatchItemsProperty(): array
    {
        $threshold = now()->subHour();

        $news = NewsPost::query()
            ->with('parish:id,name')
            ->where('status', 'published')
            ->whereNull('push_notification_sent_at')
            ->where(function ($query) use ($threshold): void {
                $query->where('published_at', '<=', $threshold)
                    ->orWhere(function ($inner) use ($threshold): void {
                        $inner->whereNull('published_at')->where('created_at', '<=', $threshold);
                    });
            })
            ->latest('published_at')
            ->limit(4)
            ->get()
            ->map(fn (NewsPost $post): array => [
                'type' => 'NEWS',
                'title' => (string) $post->title,
                'parish' => (string) ($post->parish?->name ?? 'Brak'),
                'when' => $post->published_at?->diffForHumans() ?? $post->created_at?->diffForHumans() ?? '-',
                'url' => NewsPostResource::getUrl('view', ['record' => $post]),
            ]);

        $announcements = AnnouncementSet::query()
            ->with('parish:id,name')
            ->where('status', 'published')
            ->whereNull('push_notification_sent_at')
            ->where(function ($query) use ($threshold): void {
                $query->where('published_at', '<=', $threshold)
                    ->orWhere(function ($inner) use ($threshold): void {
                        $inner->whereNull('published_at')->where('created_at', '<=', $threshold);
                    });
            })
            ->latest('published_at')
            ->limit(4)
            ->get()
            ->map(fn (AnnouncementSet $set): array => [
                'type' => 'OGLOSZENIA',
                'title' => (string) $set->title,
                'parish' => (string) ($set->parish?->name ?? 'Brak'),
                'when' => $set->published_at?->diffForHumans() ?? $set->created_at?->diffForHumans() ?? '-',
                'url' => AnnouncementSetResource::getUrl('view', ['record' => $set]),
            ]);

        return $news
            ->concat($announcements)
            ->take(8)
            ->values()
            ->all();
    }

    public function getRecentFailedPushesProperty(): array
    {
        return PushDelivery::query()
            ->where('status', PushDelivery::STATUS_FAILED)
            ->with(['user:id,email', 'device:id,platform'])
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (PushDelivery $delivery): array => [
                'id' => $delivery->getKey(),
                'type' => (string) $delivery->type,
                'user' => (string) ($delivery->user?->email ?? 'Brak'),
                'platform' => strtoupper((string) ($delivery->device?->platform ?? $delivery->platform ?? '-')),
                'error' => (string) str($delivery->error_message ?: $delivery->error_code ?: 'Nieznany blad')->limit(96),
                'created_at' => $delivery->created_at?->format('d.m.Y H:i') ?? '-',
                'url' => PushDeliveryResource::getUrl('view', ['record' => $delivery]),
            ])
            ->all();
    }

    public function getOpenConversationsProperty(): array
    {
        return OfficeConversation::query()
            ->with(['parish:id,name', 'parishioner:id,full_name,name,email', 'latestMessage'])
            ->where('status', OfficeConversation::STATUS_OPEN)
            ->withCount([
                'messages as unread_messages_count' => fn ($query) => $query
                    ->whereNull('read_by_priest_at')
                    ->whereColumn('sender_user_id', '!=', 'office_conversations.priest_user_id'),
            ])
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (OfficeConversation $conversation): array => [
                'id' => $conversation->getKey(),
                'subject' => (string) ($conversation->latestMessage?->body
                    ? str($conversation->latestMessage->body)->limit(68)
                    : 'Konwersacja kancelarii'),
                'parish' => (string) ($conversation->parish?->name ?? 'Brak'),
                'user' => (string) ($conversation->parishioner?->full_name ?: $conversation->parishioner?->name ?: $conversation->parishioner?->email ?: 'Brak'),
                'unread' => (int) ($conversation->unread_messages_count ?? 0),
                'updated_at' => $conversation->updated_at?->format('d.m.Y H:i') ?? '-',
            ])
            ->all();
    }

    public function getUpcomingMassesProperty(): array
    {
        return Mass::query()
            ->with('parish:id,name')
            ->withCount('participants')
            ->where('status', 'scheduled')
            ->whereBetween('celebration_at', [now(), now()->addDays(3)])
            ->orderBy('celebration_at')
            ->limit(8)
            ->get()
            ->map(fn (Mass $mass): array => [
                'id' => $mass->getKey(),
                'title' => (string) $mass->intention_title,
                'parish' => (string) ($mass->parish?->name ?? 'Brak'),
                'celebration_at' => $mass->celebration_at?->format('d.m.Y H:i') ?? '-',
                'participants' => (int) ($mass->participants_count ?? 0),
                'url' => MassResource::getUrl('view', ['record' => $mass]),
            ])
            ->all();
    }

    public function getSystemSnapshotProperty(): array
    {
        return [
            ['label' => 'APP_ENV', 'value' => app()->environment()],
            ['label' => 'APP_DEBUG', 'value' => config('app.debug') ? 'true' : 'false'],
            ['label' => 'PHP', 'value' => PHP_VERSION],
            ['label' => 'Laravel', 'value' => app()->version()],
            ['label' => 'Queue', 'value' => (string) config('queue.default')],
            ['label' => 'Cache', 'value' => (string) config('cache.default')],
            ['label' => 'Failed jobs', 'value' => (string) $this->failedJobsCount()],
            ['label' => 'Pushable devices', 'value' => (string) UserDevice::query()->pushable()->count()],
        ];
    }

    private function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    private function jobsCount(): int
    {
        $table = (string) config('queue.connections.database.table', 'jobs');

        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    /**
     * @return array{bucket:string,steps:int,start:Carbon,start_label_format:string,end_label_format:string,title:string}
     */
    private function resolveTrendRangeConfig(): array
    {
        return match ($this->trendRange) {
            '30d' => [
                'bucket' => 'day',
                'steps' => 30,
                'start' => now()->startOfDay()->subDays(29),
                'start_label_format' => 'd.m',
                'end_label_format' => 'd.m',
                'title' => '30 dni',
            ],
            '7d' => [
                'bucket' => 'day',
                'steps' => 7,
                'start' => now()->startOfDay()->subDays(6),
                'start_label_format' => 'd.m',
                'end_label_format' => 'd.m',
                'title' => '7 dni',
            ],
            default => [
                'bucket' => 'hour',
                'steps' => 24,
                'start' => now()->startOfHour()->subHours(23),
                'start_label_format' => 'H:i',
                'end_label_format' => 'H:i',
                'title' => '24h',
            ],
        };
    }

    /**
     * @return Collection<int,mixed>
     */
    private function fetchTableTimestamps(string $table, string $column, Carbon $start): Collection
    {
        if (! Schema::hasTable($table)) {
            return collect();
        }

        return DB::table($table)
            ->where($column, '>=', $start)
            ->pluck($column)
            ->filter();
    }

    /**
     * @return array<int,string>
     */
    private function buildLabels(Carbon $start, int $steps, string $unit, string $format): array
    {
        $labels = [];

        for ($index = 0; $index < $steps; $index++) {
            $labels[] = $start->copy()->add($index, $unit)->format($format);
        }

        return $labels;
    }

    /**
     * @param  iterable<int,mixed>  $timestamps
     * @return array<int,int>
     */
    private function bucketizeTimestamps(iterable $timestamps, Carbon $start, int $steps, string $unit): array
    {
        $values = array_fill(0, $steps, 0);

        foreach ($timestamps as $timestamp) {
            if (blank($timestamp)) {
                continue;
            }

            $moment = $timestamp instanceof Carbon
                ? $timestamp->copy()
                : Carbon::parse((string) $timestamp);

            if ($moment->lt($start)) {
                continue;
            }

            $index = (int) match ($unit) {
                'day' => $start->diffInDays($moment),
                default => $start->diffInHours($moment),
            };

            if (($index < 0) || ($index >= $steps)) {
                continue;
            }

            $values[$index]++;
        }

        return $values;
    }

    /**
     * @param  array<int,array{key:string,label:string,tone:string,values:array<int,int>}>  $series
     * @return array<int,array<string,mixed>>
     */
    private function normalizeTrendSeries(array $series): array
    {
        $globalMax = max(1, ...array_map(
            fn (array $row): int => max(1, ...$row['values']),
            $series,
        ));

        return array_map(function (array $row) use ($globalMax): array {
            $values = $row['values'];

            return [
                ...$row,
                'total' => array_sum($values),
                'latest' => $values[array_key_last($values)] ?? 0,
                'peak' => max($values),
                'points' => $this->buildSparklinePoints($values, 320, 88, $globalMax),
            ];
        }, $series);
    }

    /**
     * @param  array{bucket:string,steps:int,start:Carbon,start_label_format:string,end_label_format:string,title:string}  $config
     * @param  array<int,array{key:string,label:string,tone:string,values:array<int,int>}>  $series
     * @return array<string,mixed>
     */
    private function buildTrendPanel(string $key, string $title, string $description, array $config, array $series): array
    {
        $normalized = $this->normalizeTrendSeries($series);
        $visible = array_values(array_filter(
            $normalized,
            fn (array $row): bool => ! in_array("{$key}.{$row['key']}", $this->hiddenTrendSeries, true),
        ));

        return [
            'key' => $key,
            'title' => $title,
            'description' => $description,
            'range_title' => $config['title'],
            'start_label' => $config['start']->format($config['start_label_format']),
            'end_label' => $config['start']->copy()->add($config['steps'] - 1, $config['bucket'])->format($config['end_label_format']),
            'series' => $normalized,
            'visible_series' => $visible,
            'has_visible_series' => $visible !== [],
        ];
    }

    /**
     * @param  array<int,int>  $values
     */
    private function buildSparklinePoints(array $values, int $width, int $height, int $max): string
    {
        $count = count($values);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return '0,'.($height - 10);
        }

        $stepX = $width / max(1, $count - 1);
        $usableHeight = $height - 16;

        return collect($values)
            ->map(function (int $value, int $index) use ($stepX, $usableHeight, $height, $max): string {
                $x = round($index * $stepX, 2);
                $ratio = $max > 0 ? ($value / $max) : 0;
                $y = round(($height - 8) - ($ratio * $usableHeight), 2);

                return "{$x},{$y}";
            })
            ->implode(' ');
    }

    /**
     * @return array{news:int,announcements:int}
     */
    private function dispatchQueueCounts(): array
    {
        $threshold = now()->subHour();

        return [
            'news' => NewsPost::query()
                ->where('status', 'published')
                ->whereNull('push_notification_sent_at')
                ->where(function ($query) use ($threshold): void {
                    $query->where('published_at', '<=', $threshold)
                        ->orWhere(function ($inner) use ($threshold): void {
                            $inner->whereNull('published_at')->where('created_at', '<=', $threshold);
                        });
                })
                ->count(),
            'announcements' => AnnouncementSet::query()
                ->where('status', 'published')
                ->whereNull('push_notification_sent_at')
                ->where(function ($query) use ($threshold): void {
                    $query->where('published_at', '<=', $threshold)
                        ->orWhere(function ($inner) use ($threshold): void {
                            $inner->whereNull('published_at')->where('created_at', '<=', $threshold);
                        });
                })
                ->count(),
        ];
    }
}
