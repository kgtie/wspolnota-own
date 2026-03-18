<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\SuperAdmin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Filament\SuperAdmin\Resources\Masses\MassResource;
use App\Filament\SuperAdmin\Resources\NewsPosts\NewsPostResource;
use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\NewsPost;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

class NotificationDispatchCenter extends Page
{
    protected static ?string $title = 'Centrum dispatchu';

    protected static ?string $navigationLabel = 'Centrum dispatchu';

    protected static string | \BackedEnum | null $navigationIcon = null;

    protected static string | \UnitEnum | null $navigationGroup = 'Komunikacja i kampanie';

    protected static ?int $navigationSort = 3;

    protected ?string $subheading = 'Operacyjny widok backlogu, opoznien, retry i recznego uruchamiania dispatchu dla contentu, mszy i maili.';

    protected string $view = 'filament.superadmin.pages.notification-dispatch-center';

    protected ?string $pollingInterval = '30s';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_delayed_content_dispatch')
                ->label('Uruchom content dispatch')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function (): void {
                    $this->runArtisanCommand(
                        'notifications:dispatch-delayed-content',
                        ['--limit' => 150],
                        'Uruchomiono dispatch news i ogloszen.',
                    );
                }),
            Action::make('run_mass_push_dispatch')
                ->label('Uruchom reminders mszy')
                ->icon('heroicon-o-bell-alert')
                ->color('info')
                ->action(function (): void {
                    $this->runArtisanCommand(
                        'masses:dispatch-pending-reminders',
                        ['--limit' => 300],
                        'Uruchomiono dispatch przypomnien push dla mszy.',
                    );
                }),
            Action::make('run_mass_digest_dispatch')
                ->label('Uruchom digest 5:00')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->runArtisanCommand(
                        'masses:dispatch-morning-email-reminders',
                        ['--limit' => 500],
                        'Uruchomiono poranny digest mszalny.',
                    );
                }),
            Action::make('retry_all_failed_mail_jobs')
                ->label('Retry wszystkich maili')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->retryAllFailedMailJobs();
                }),
        ];
    }

    public function getHeroCardsProperty(): array
    {
        $pendingNews = $this->pendingNewsQuery()->count();
        $pendingAnnouncements = $this->pendingAnnouncementSetsQuery()->count();
        $massSummary = $this->massReminderSummary;
        $failedMailJobs = count($this->failedMailJobs);
        $failedQueue = $this->failedJobsCount();
        $critical = $pendingNews + $pendingAnnouncements + $failedMailJobs + ($massSummary['due_total'] ?? 0);

        return [
            [
                'label' => 'Krytyczne pozycje',
                'value' => number_format($critical, 0, ',', ' '),
                'hint' => 'Laczna liczba zaleglych content dispatch, reminderow i failed mail jobs.',
                'tone' => $critical > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Content backlog',
                'value' => number_format($pendingNews + $pendingAnnouncements, 0, ',', ' '),
                'hint' => "News: {$pendingNews} · ogloszenia: {$pendingAnnouncements}",
                'tone' => ($pendingNews + $pendingAnnouncements) > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Remindery mszy teraz',
                'value' => number_format((int) ($massSummary['due_total'] ?? 0), 0, ',', ' '),
                'hint' => "24h: {$massSummary['due_24h']} · 8h: {$massSummary['due_8h']} · 1h: {$massSummary['due_1h']}",
                'tone' => ($massSummary['due_total'] ?? 0) > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Digests 5:00',
                'value' => number_format((int) ($massSummary['due_digest_users'] ?? 0), 0, ',', ' '),
                'hint' => 'Uzytkownicy, ktorzy nadal czekaja na dzisiejszy digest mszalny.',
                'tone' => ($massSummary['due_digest_users'] ?? 0) > 0 ? 'info' : 'success',
            ],
            [
                'label' => 'Failed mail jobs',
                'value' => number_format($failedMailJobs, 0, ',', ' '),
                'hint' => 'Nieudane jobs mailowe wymagajace retry albo czyszczenia.',
                'tone' => $failedMailJobs > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Queue failed globalnie',
                'value' => number_format($failedQueue, 0, ',', ' '),
                'hint' => 'Wszystkie rekordy z failed_jobs, nie tylko mailowe.',
                'tone' => $failedQueue > 0 ? 'warning' : 'success',
            ],
        ];
    }

    public function getQuickStatsProperty(): array
    {
        $pendingNews = $this->pendingNewsQuery()->count();
        $pendingAnnouncements = $this->pendingAnnouncementSetsQuery()->count();
        $oldestNewsDelay = $this->oldestDelayInMinutes($this->pendingNewsQuery(), 'published_at');
        $oldestAnnouncementsDelay = $this->oldestDelayInMinutes($this->pendingAnnouncementSetsQuery(), 'published_at');

        return [
            [
                'label' => 'Najstarszy news w backlogu',
                'value' => $oldestNewsDelay ? "{$oldestNewsDelay} min" : 'brak',
                'meta' => $pendingNews > 0 ? "rekordow: {$pendingNews}" : 'brak oczekujacych',
                'tone' => $oldestNewsDelay && $oldestNewsDelay >= 120 ? 'danger' : ($pendingNews > 0 ? 'warning' : 'success'),
            ],
            [
                'label' => 'Najstarsze ogloszenia w backlogu',
                'value' => $oldestAnnouncementsDelay ? "{$oldestAnnouncementsDelay} min" : 'brak',
                'meta' => $pendingAnnouncements > 0 ? "rekordow: {$pendingAnnouncements}" : 'brak oczekujacych',
                'tone' => $oldestAnnouncementsDelay && $oldestAnnouncementsDelay >= 120 ? 'danger' : ($pendingAnnouncements > 0 ? 'warning' : 'success'),
            ],
            [
                'label' => 'Mail retry kandydaci',
                'value' => (string) count($this->failedMailJobs),
                'meta' => 'Retry wszystkich maili jest dostepne z naglowka strony.',
                'tone' => count($this->failedMailJobs) > 0 ? 'danger' : 'success',
            ],
        ];
    }

    public function getPendingNewsProperty(): array
    {
        return $this->pendingNewsQuery()
            ->with('parish:id,name')
            ->orderByRaw('COALESCE(published_at, created_at) asc')
            ->limit(10)
            ->get()
            ->map(fn (NewsPost $post): array => [
                'id' => $post->getKey(),
                'title' => (string) $post->title,
                'parish' => (string) ($post->parish?->name ?? 'Brak'),
                'published_at' => optional($post->published_at ?? $post->created_at)->format('d.m.Y H:i'),
                'delay_minutes' => $this->delayMinutes($post->published_at ?? $post->created_at),
                'status' => $this->contentDispatchState($post->push_notification_sent_at, $post->email_notification_sent_at),
                'url' => NewsPostResource::getUrl('view', ['record' => $post]),
            ])
            ->all();
    }

    public function getPendingAnnouncementSetsProperty(): array
    {
        return $this->pendingAnnouncementSetsQuery()
            ->with('parish:id,name')
            ->orderByRaw('COALESCE(published_at, created_at) asc')
            ->limit(10)
            ->get()
            ->map(fn (AnnouncementSet $set): array => [
                'id' => $set->getKey(),
                'title' => (string) $set->title,
                'parish' => (string) ($set->parish?->name ?? 'Brak'),
                'published_at' => optional($set->published_at ?? $set->created_at)->format('d.m.Y H:i'),
                'delay_minutes' => $this->delayMinutes($set->published_at ?? $set->created_at),
                'status' => $this->contentDispatchState($set->push_notification_sent_at, $set->email_notification_sent_at),
                'url' => AnnouncementSetResource::getUrl('view', ['record' => $set]),
            ])
            ->all();
    }

    public function getRecentlyDispatchedContentProperty(): array
    {
        $news = NewsPost::query()
            ->with('parish:id,name')
            ->whereNotNull('push_notification_sent_at')
            ->latest('push_notification_sent_at')
            ->limit(6)
            ->get()
            ->map(fn (NewsPost $post): array => [
                'type' => 'NEWS_CREATED',
                'title' => (string) $post->title,
                'parish' => (string) ($post->parish?->name ?? 'Brak'),
                'dispatched_at' => optional($post->push_notification_sent_at)->format('d.m.Y H:i'),
                'url' => NewsPostResource::getUrl('view', ['record' => $post]),
            ]);

        $announcements = AnnouncementSet::query()
            ->with('parish:id,name')
            ->whereNotNull('push_notification_sent_at')
            ->latest('push_notification_sent_at')
            ->limit(6)
            ->get()
            ->map(fn (AnnouncementSet $set): array => [
                'type' => 'ANNOUNCEMENTS_PACKAGE_PUBLISHED',
                'title' => (string) $set->title,
                'parish' => (string) ($set->parish?->name ?? 'Brak'),
                'dispatched_at' => optional($set->push_notification_sent_at)->format('d.m.Y H:i'),
                'url' => AnnouncementSetResource::getUrl('view', ['record' => $set]),
            ]);

        return $news
            ->concat($announcements)
            ->sortByDesc('dispatched_at')
            ->take(8)
            ->values()
            ->all();
    }

    public function getMassReminderSummaryProperty(): array
    {
        if (! Schema::hasTable('mass_user')) {
            return [
                'due_24h' => 0,
                'due_8h' => 0,
                'due_1h' => 0,
                'due_total' => 0,
                'due_digest_users' => 0,
            ];
        }

        $masses = Mass::query()
            ->with(['participants' => fn ($query) => $query->where('users.status', 'active')])
            ->where('status', 'scheduled')
            ->whereBetween('celebration_at', [now(), now()->addDay()])
            ->get();

        $due24h = 0;
        $due8h = 0;
        $due1h = 0;

        foreach ($masses as $mass) {
            $hoursUntilMass = now()->diffInRealHours($mass->celebration_at, false);

            foreach ($mass->participants as $participant) {
                if ($hoursUntilMass <= 24 && $hoursUntilMass > 8 && ! $participant->pivot?->reminder_push_24h_sent_at) {
                    $due24h++;
                }

                if ($hoursUntilMass <= 8 && $hoursUntilMass > 1 && ! $participant->pivot?->reminder_push_8h_sent_at) {
                    $due8h++;
                }

                if ($hoursUntilMass <= 1 && $hoursUntilMass > 0 && ! $participant->pivot?->reminder_push_1h_sent_at) {
                    $due1h++;
                }
            }
        }

        $todayDigestUsers = DB::table('mass_user')
            ->join('masses', 'masses.id', '=', 'mass_user.mass_id')
            ->join('users', 'users.id', '=', 'mass_user.user_id')
            ->where('users.status', 'active')
            ->where('masses.status', 'scheduled')
            ->whereDate('masses.celebration_at', now()->toDateString())
            ->whereNull('mass_user.reminder_email_sent_at')
            ->distinct('mass_user.user_id')
            ->count('mass_user.user_id');

        return [
            'due_24h' => $due24h,
            'due_8h' => $due8h,
            'due_1h' => $due1h,
            'due_total' => $due24h + $due8h + $due1h,
            'due_digest_users' => (int) $todayDigestUsers,
        ];
    }

    public function getUpcomingMassesProperty(): array
    {
        return Mass::query()
            ->with(['parish:id,name', 'participants' => fn ($query) => $query->where('users.status', 'active')])
            ->where('status', 'scheduled')
            ->whereBetween('celebration_at', [now(), now()->addDays(3)])
            ->orderBy('celebration_at')
            ->limit(12)
            ->get()
            ->map(function (Mass $mass): array {
                $hoursUntilMass = now()->diffInRealHours($mass->celebration_at, false);

                $due24h = 0;
                $due8h = 0;
                $due1h = 0;
                $digestPending = 0;

                foreach ($mass->participants as $participant) {
                    if ($hoursUntilMass <= 24 && $hoursUntilMass > 8 && ! $participant->pivot?->reminder_push_24h_sent_at) {
                        $due24h++;
                    }

                    if ($hoursUntilMass <= 8 && $hoursUntilMass > 1 && ! $participant->pivot?->reminder_push_8h_sent_at) {
                        $due8h++;
                    }

                    if ($hoursUntilMass <= 1 && $hoursUntilMass > 0 && ! $participant->pivot?->reminder_push_1h_sent_at) {
                        $due1h++;
                    }

                    if ($mass->celebration_at?->isToday() && ! $participant->pivot?->reminder_email_sent_at) {
                        $digestPending++;
                    }
                }

                $participants = $mass->participants->count();
                $sent24h = $mass->participants->filter(fn ($participant) => filled($participant->pivot?->reminder_push_24h_sent_at))->count();
                $sent8h = $mass->participants->filter(fn ($participant) => filled($participant->pivot?->reminder_push_8h_sent_at))->count();
                $sent1h = $mass->participants->filter(fn ($participant) => filled($participant->pivot?->reminder_push_1h_sent_at))->count();
                $digestSent = $mass->participants->filter(fn ($participant) => filled($participant->pivot?->reminder_email_sent_at))->count();

                return [
                    'id' => $mass->getKey(),
                    'parish' => (string) ($mass->parish?->name ?? 'Brak'),
                    'title' => (string) $mass->intention_title,
                    'celebration_at' => $mass->celebration_at?->format('d.m.Y H:i') ?? '-',
                    'hours_until' => $hoursUntilMass,
                    'participants' => $participants,
                    'due_24h' => $due24h,
                    'due_8h' => $due8h,
                    'due_1h' => $due1h,
                    'digest_pending' => $digestPending,
                    'sent_24h' => $sent24h,
                    'sent_8h' => $sent8h,
                    'sent_1h' => $sent1h,
                    'digest_sent' => $digestSent,
                    'url' => MassResource::getUrl('view', ['record' => $mass]),
                ];
            })
            ->all();
    }

    public static function getNavigationBadge(): ?string
    {
        $instance = app(static::class);
        $count = count($instance->pendingNews) + count($instance->pendingAnnouncementSets) + count($instance->failedMailJobs);

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() !== null ? 'warning' : 'success';
    }

    public function getFailedMailJobsProperty(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(80)
            ->get()
            ->map(fn (stdClass $row): ?array => $this->parseFailedMailJob($row))
            ->filter()
            ->values()
            ->all();
    }

    public function getFailedMailStatsProperty(): array
    {
        $rows = collect($this->failedMailJobs)
            ->groupBy('type')
            ->map(fn (Collection $jobs): array => [
                'type' => (string) $jobs->first()['type'],
                'count' => $jobs->count(),
                'latest_failed_at' => (string) $jobs->first()['failed_at'],
            ])
            ->sortByDesc('count')
            ->values();

        return $rows->all();
    }

    public function retryFailedJob(int $jobId): void
    {
        Artisan::call('queue:retry', ['id' => [$jobId]]);

        Notification::make()
            ->success()
            ->title("Ponowiono job #{$jobId}.")
            ->send();
    }

    public function forgetFailedJob(int $jobId): void
    {
        Artisan::call('queue:forget', ['id' => $jobId]);

        Notification::make()
            ->success()
            ->title("Usunieto failed job #{$jobId}.")
            ->send();
    }

    public function retryAllFailedMailJobs(): void
    {
        $ids = collect($this->failedMailJobs)->pluck('id')->map(fn ($id) => (string) $id)->all();

        if ($ids === []) {
            Notification::make()
                ->warning()
                ->title('Brak failed mail jobs do retry.')
                ->send();

            return;
        }

        Artisan::call('queue:retry', ['id' => $ids]);

        Notification::make()
            ->success()
            ->title('Zakolejkowano retry dla wszystkich failed mail jobs.')
            ->body('Liczba jobow: '.count($ids))
            ->send();
    }

    private function pendingNewsQuery(): Builder
    {
        $threshold = now()->subHour();

        return NewsPost::query()
            ->where('status', 'published')
            ->where(function ($query): void {
                $query
                    ->whereNull('push_notification_sent_at')
                    ->orWhereNull('email_notification_sent_at');
            })
            ->where(function ($query) use ($threshold): void {
                $query
                    ->where('published_at', '<=', $threshold)
                    ->orWhere(function ($inner) use ($threshold): void {
                        $inner
                            ->whereNull('published_at')
                            ->where('created_at', '<=', $threshold);
                    });
            });
    }

    private function pendingAnnouncementSetsQuery(): Builder
    {
        $threshold = now()->subHour();

        return AnnouncementSet::query()
            ->where('status', 'published')
            ->where(function ($query): void {
                $query
                    ->whereNull('push_notification_sent_at')
                    ->orWhereNull('email_notification_sent_at');
            })
            ->where(function ($query) use ($threshold): void {
                $query
                    ->where('published_at', '<=', $threshold)
                    ->orWhere(function ($inner) use ($threshold): void {
                        $inner
                            ->whereNull('published_at')
                            ->where('created_at', '<=', $threshold);
                    });
            });
    }

    private function dueMassReminderCount(): int
    {
        return (int) ($this->massReminderSummary['due_total'] ?? 0);
    }

    private function failedJobsCount(): int
    {
        $table = config('queue.failed.table', 'failed_jobs');

        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    private function parseFailedMailJob(stdClass $row): ?array
    {
        $payload = json_decode((string) $row->payload, true);

        if (! is_array($payload)) {
            return null;
        }

        $displayName = (string) ($payload['displayName'] ?? '');
        $type = $this->mapMailDisplayNameToType($displayName);

        if ($type === null) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'type' => $type,
            'display_name' => $displayName,
            'queue' => (string) $row->queue,
            'failed_at' => is_string($row->failed_at) ? $row->failed_at : (string) $row->failed_at,
            'exception_headline' => str((string) $row->exception)->before("\n")->limit(180)->toString(),
        ];
    }

    private function mapMailDisplayNameToType(string $displayName): ?string
    {
        return match ($displayName) {
            'App\\Notifications\\NewsPublishedMailNotification' => 'NEWS_CREATED',
            'App\\Notifications\\AnnouncementPackagePublishedMailNotification' => 'ANNOUNCEMENTS_PACKAGE_PUBLISHED',
            'App\\Notifications\\MassPendingReminderMailNotification',
            'App\\Notifications\\MassPendingDailyDigestMailNotification' => 'MASS_PENDING',
            'App\\Notifications\\OfficeMessageReceivedMailNotification' => 'OFFICE_MESSAGE_RECEIVED',
            'App\\Notifications\\ParishApprovalStatusChangedMailNotification' => 'PARISH_APPROVAL_STATUS_CHANGED',
            'App\\Mail\\CommunicationBroadcastMessage' => 'COMMUNICATION_BROADCAST',
            default => null,
        };
    }

    private function runArtisanCommand(string $command, array $arguments, string $title): void
    {
        Artisan::call($command, $arguments);

        $output = trim((string) Artisan::output());

        Notification::make()
            ->success()
            ->title($title)
            ->body($output !== '' ? str($output)->squish()->limit(220)->toString() : null)
            ->send();
    }

    private function oldestDelayInMinutes(Builder $query, string $column): ?int
    {
        $record = (clone $query)->orderByRaw("COALESCE({$column}, created_at) asc")->first();
        $timestamp = $record?->{$column} ?? $record?->created_at;

        return $timestamp ? $this->delayMinutes($timestamp) : null;
    }

    private function delayMinutes(mixed $timestamp): int
    {
        return (int) max(0, now()->diffInRealMinutes($timestamp));
    }

    private function contentDispatchState(mixed $pushSentAt, mixed $emailSentAt): array
    {
        $pushDone = filled($pushSentAt);
        $emailDone = filled($emailSentAt);

        if ($pushDone && $emailDone) {
            return ['label' => 'Komplet', 'tone' => 'success'];
        }

        if ($pushDone || $emailDone) {
            return ['label' => 'Czesciowy', 'tone' => 'warning'];
        }

        return ['label' => 'Pending', 'tone' => 'danger'];
    }
}
