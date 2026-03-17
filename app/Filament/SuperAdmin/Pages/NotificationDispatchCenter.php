<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\NewsPost;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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

    protected ?string $subheading = 'Status wysylek news, ogloszen i przypomnien mszalnych oraz retry dla nieudanych maili.';

    protected string $view = 'filament.superadmin.pages.notification-dispatch-center';

    protected ?string $pollingInterval = '30s';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retry_all_failed_mail_jobs')
                ->label('Retry wszystkich maili')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn () => $this->retryAllFailedMailJobs()),
        ];
    }

    public function getDispatchCardsProperty(): array
    {
        $threshold = now()->subHour();

        return [
            [
                'label' => 'News gotowe do dispatchu',
                'value' => NewsPost::query()
                    ->where('status', 'published')
                    ->whereNull('push_notification_sent_at')
                    ->where(function ($query) use ($threshold): void {
                        $query->where('published_at', '<=', $threshold)
                            ->orWhere(function ($inner) use ($threshold): void {
                                $inner->whereNull('published_at')->where('created_at', '<=', $threshold);
                            });
                    })
                    ->count(),
            ],
            [
                'label' => 'Ogloszenia gotowe do dispatchu',
                'value' => AnnouncementSet::query()
                    ->where('status', 'published')
                    ->whereNull('push_notification_sent_at')
                    ->where(function ($query) use ($threshold): void {
                        $query->where('published_at', '<=', $threshold)
                            ->orWhere(function ($inner) use ($threshold): void {
                                $inner->whereNull('published_at')->where('created_at', '<=', $threshold);
                            });
                    })
                    ->count(),
            ],
            [
                'label' => 'Przypomnienia mszy do wyslania',
                'value' => $this->dueMassReminderCount(),
            ],
            [
                'label' => 'Failed maile',
                'value' => count($this->failedMailJobs),
            ],
        ];
    }

    public function getRecentNewsProperty(): array
    {
        return NewsPost::query()
            ->with('parish:id,name')
            ->where('status', 'published')
            ->latest('published_at')
            ->limit(12)
            ->get()
            ->map(fn (NewsPost $post): array => [
                'id' => $post->getKey(),
                'title' => (string) $post->title,
                'parish' => (string) ($post->parish?->name ?? 'Brak'),
                'published_at' => $post->published_at?->format('d.m.Y H:i') ?? '-',
                'push_sent_at' => $post->push_notification_sent_at?->format('d.m.Y H:i') ?? 'oczekuje',
                'email_sent_at' => $post->email_notification_sent_at?->format('d.m.Y H:i') ?? 'oczekuje',
            ])
            ->all();
    }

    public function getRecentAnnouncementSetsProperty(): array
    {
        return AnnouncementSet::query()
            ->with('parish:id,name')
            ->where('status', 'published')
            ->latest('published_at')
            ->limit(12)
            ->get()
            ->map(fn (AnnouncementSet $set): array => [
                'id' => $set->getKey(),
                'title' => (string) $set->title,
                'parish' => (string) ($set->parish?->name ?? 'Brak'),
                'published_at' => $set->published_at?->format('d.m.Y H:i') ?? '-',
                'push_sent_at' => $set->push_notification_sent_at?->format('d.m.Y H:i') ?? 'oczekuje',
                'email_sent_at' => $set->email_notification_sent_at?->format('d.m.Y H:i') ?? 'oczekuje',
            ])
            ->all();
    }

    public function getUpcomingMassesProperty(): array
    {
        return Mass::query()
            ->with('parish:id,name')
            ->withCount([
                'participants',
                'participants as reminder_push_24h_count' => fn ($query) => $query->whereNotNull('mass_user.reminder_push_24h_sent_at'),
                'participants as reminder_push_8h_count' => fn ($query) => $query->whereNotNull('mass_user.reminder_push_8h_sent_at'),
                'participants as reminder_push_1h_count' => fn ($query) => $query->whereNotNull('mass_user.reminder_push_1h_sent_at'),
                'participants as reminder_email_count' => fn ($query) => $query->whereNotNull('mass_user.reminder_email_sent_at'),
            ])
            ->where('status', 'scheduled')
            ->whereBetween('celebration_at', [now(), now()->addDays(3)])
            ->orderBy('celebration_at')
            ->limit(12)
            ->get()
            ->map(fn (Mass $mass): array => [
                'id' => $mass->getKey(),
                'parish' => (string) ($mass->parish?->name ?? 'Brak'),
                'title' => (string) $mass->intention_title,
                'celebration_at' => $mass->celebration_at?->format('d.m.Y H:i') ?? '-',
                'participants' => (int) ($mass->participants_count ?? 0),
                'push_24h' => (int) ($mass->reminder_push_24h_count ?? 0),
                'push_8h' => (int) ($mass->reminder_push_8h_count ?? 0),
                'push_1h' => (int) ($mass->reminder_push_1h_count ?? 0),
                'email' => (int) ($mass->reminder_email_count ?? 0),
            ])
            ->all();
    }

    public static function getNavigationBadge(): ?string
    {
        $threshold = now()->subHour();

        $pendingNews = NewsPost::query()
            ->where('status', 'published')
            ->whereNull('push_notification_sent_at')
            ->where(function ($query) use ($threshold): void {
                $query->where('published_at', '<=', $threshold)
                    ->orWhere(function ($inner) use ($threshold): void {
                        $inner->whereNull('published_at')->where('created_at', '<=', $threshold);
                    });
            })
            ->count();

        $pendingAnnouncements = AnnouncementSet::query()
            ->where('status', 'published')
            ->whereNull('push_notification_sent_at')
            ->where(function ($query) use ($threshold): void {
                $query->where('published_at', '<=', $threshold)
                    ->orWhere(function ($inner) use ($threshold): void {
                        $inner->whereNull('published_at')->where('created_at', '<=', $threshold);
                    });
            })
            ->count();

        $failedMailJobs = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')
                ->get()
                ->filter(function (stdClass $row): bool {
                    $payload = json_decode((string) $row->payload, true);
                    $displayName = is_array($payload) ? (string) ($payload['displayName'] ?? '') : '';

                    return str_contains($displayName, 'Mail')
                        || str_contains($displayName, 'Mailable')
                        || str_contains($displayName, 'CommunicationBroadcastMessage');
                })
                ->count()
            : 0;

        $count = $pendingNews + $pendingAnnouncements + $failedMailJobs;

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

    private function dueMassReminderCount(): int
    {
        if (! Schema::hasTable('mass_user')) {
            return 0;
        }

        $windowStart = now();
        $windowEnd = now()->addDay();

        return (int) DB::table('mass_user')
            ->join('masses', 'masses.id', '=', 'mass_user.mass_id')
            ->where('masses.status', 'scheduled')
            ->whereBetween('masses.celebration_at', [$windowStart, $windowEnd])
            ->where(function ($query): void {
                $query
                    ->whereNull('mass_user.reminder_push_24h_sent_at')
                    ->orWhereNull('mass_user.reminder_push_8h_sent_at')
                    ->orWhereNull('mass_user.reminder_push_1h_sent_at')
                    ->orWhereNull('mass_user.reminder_email_sent_at');
            })
            ->count();
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
            'App\\Notifications\\MassPendingReminderMailNotification' => 'MASS_PENDING',
            'App\\Notifications\\OfficeMessageReceivedMailNotification' => 'OFFICE_MESSAGE_RECEIVED',
            'App\\Notifications\\ParishApprovalStatusChangedMailNotification' => 'PARISH_APPROVAL_STATUS_CHANGED',
            'App\\Mail\\CommunicationBroadcastMessage' => 'COMMUNICATION_BROADCAST',
            default => null,
        };
    }
}
