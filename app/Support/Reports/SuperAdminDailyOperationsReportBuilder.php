<?php

namespace App\Support\Reports;

use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\NewsPost;
use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\Parish;
use App\Models\PushDelivery;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SuperAdminDailyOperationsReportBuilder
{
    public function build(CarbonInterface $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $newUsers = User::query()
            ->with('homeParish')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $publishedNews = NewsPost::query()
            ->with('parish')
            ->where('status', 'published')
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('published_at', [$start, $end])
                    ->orWhere(function ($inner) use ($start, $end): void {
                        $inner->whereNull('published_at')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->orderByRaw('COALESCE(published_at, created_at) desc')
            ->get();

        $publishedAnnouncements = AnnouncementSet::query()
            ->with('parish')
            ->where('status', 'published')
            ->whereBetween('published_at', [$start, $end])
            ->orderByDesc('published_at')
            ->get();

        $massesCreated = Mass::query()
            ->with('parish')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('celebration_at')
            ->get();

        $massesCelebrated = Mass::query()
            ->with('parish')
            ->whereBetween('celebration_at', [$start, $end])
            ->orderBy('celebration_at')
            ->get();

        $newConversations = OfficeConversation::query()
            ->with(['parish', 'parishioner', 'priest'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $officeMessages = OfficeMessage::query()
            ->with(['conversation.parish', 'sender'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $pushDeliveries = PushDelivery::query()
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->get();

        $activities = Activity::query()
            ->with('causer')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $failedJobs = collect(
            DB::table('failed_jobs')
                ->whereBetween('failed_at', [$start, $end])
                ->orderByDesc('failed_at')
                ->limit(25)
                ->get()
        );

        $notifications = collect(
            DB::table('notifications')
                ->whereBetween('created_at', [$start, $end])
                ->get()
        );

        $mediaCreated = Media::query()
            ->whereBetween('created_at', [$start, $end])
            ->get();

        return [
            'date_label' => $start->format('d.m.Y'),
            'window' => [
                'start' => $start,
                'end' => $end,
            ],
            'overview' => [
                'new_users' => $newUsers->count(),
                'verified_users' => User::query()->whereBetween('user_verified_at', [$start, $end])->count(),
                'new_parishes' => Parish::query()->whereBetween('created_at', [$start, $end])->count(),
                'published_news' => $publishedNews->count(),
                'published_announcements' => $publishedAnnouncements->count(),
                'masses_created' => $massesCreated->count(),
                'masses_celebrated' => $massesCelebrated->count(),
                'office_conversations' => $newConversations->count(),
                'office_messages' => $officeMessages->count(),
                'push_sent' => $pushDeliveries->where('status', PushDelivery::STATUS_SENT)->count(),
                'push_failed' => $pushDeliveries->where('status', PushDelivery::STATUS_FAILED)->count(),
                'notification_items' => $notifications->count(),
                'failed_jobs' => $failedJobs->count(),
                'activity_entries' => $activities->count(),
                'media_uploaded' => $mediaCreated->count(),
            ],
            'users' => [
                'items' => $newUsers->take(20)->map(fn (User $user): array => [
                    'name' => $user->full_name ?: $user->name ?: $user->email,
                    'email' => $user->email,
                    'role' => $this->mapRoleLabel((int) $user->role),
                    'parish' => $user->homeParish?->name,
                    'verified' => (bool) $user->is_user_verified,
                ])->values()->all(),
                'by_role' => [
                    'parafianie' => $newUsers->where('role', 0)->count(),
                    'administratorzy' => $newUsers->where('role', 1)->count(),
                    'superadmini' => $newUsers->where('role', 2)->count(),
                ],
            ],
            'content' => [
                'news' => $publishedNews->take(20)->map(fn (NewsPost $news): array => [
                    'title' => $news->title,
                    'parish' => $news->parish?->name,
                    'published_at' => ($news->published_at ?? $news->created_at)?->format('d.m.Y H:i'),
                ])->values()->all(),
                'announcements' => $publishedAnnouncements->take(20)->map(fn (AnnouncementSet $set): array => [
                    'title' => $set->title,
                    'parish' => $set->parish?->name,
                    'effective' => trim(($set->effective_from?->format('d.m.Y') ?? '').' - '.($set->effective_to?->format('d.m.Y') ?? '')),
                    'published_at' => $set->published_at?->format('d.m.Y H:i'),
                ])->values()->all(),
                'masses_created' => $massesCreated->take(20)->map(fn (Mass $mass): array => [
                    'parish' => $mass->parish?->name,
                    'celebration_at' => $mass->celebration_at?->format('d.m.Y H:i'),
                    'intention_title' => $mass->intention_title,
                    'status' => $mass->status,
                ])->values()->all(),
                'masses_celebrated' => $massesCelebrated->take(20)->map(fn (Mass $mass): array => [
                    'parish' => $mass->parish?->name,
                    'celebration_at' => $mass->celebration_at?->format('d.m.Y H:i'),
                    'intention_title' => $mass->intention_title,
                    'status' => $mass->status,
                ])->values()->all(),
            ],
            'office' => [
                'open_total_now' => OfficeConversation::query()->where('status', OfficeConversation::STATUS_OPEN)->count(),
                'closed_in_window' => OfficeConversation::query()->whereBetween('closed_at', [$start, $end])->count(),
                'new_conversations' => $newConversations->take(20)->map(fn (OfficeConversation $conversation): array => [
                    'parish' => $conversation->parish?->name,
                    'parishioner' => $conversation->parishioner?->full_name ?: $conversation->parishioner?->name ?: $conversation->parishioner?->email,
                    'priest' => $conversation->priest?->full_name ?: $conversation->priest?->name ?: $conversation->priest?->email,
                    'status' => $conversation->status,
                    'created_at' => $conversation->created_at?->format('d.m.Y H:i'),
                ])->values()->all(),
                'messages' => $officeMessages->take(25)->map(fn (OfficeMessage $message): array => [
                    'parish' => $message->conversation?->parish?->name,
                    'sender' => $message->sender?->full_name ?: $message->sender?->name ?: $message->sender?->email,
                    'created_at' => $message->created_at?->format('d.m.Y H:i'),
                    'has_attachments' => (bool) $message->has_attachments,
                ])->values()->all(),
            ],
            'push' => [
                'by_type' => $pushDeliveries
                    ->groupBy(fn (PushDelivery $delivery): string => $delivery->type ?: 'unknown')
                    ->map(fn ($rows): array => [
                        'sent' => $rows->where('status', PushDelivery::STATUS_SENT)->count(),
                        'failed' => $rows->where('status', PushDelivery::STATUS_FAILED)->count(),
                        'queued' => $rows->where('status', PushDelivery::STATUS_QUEUED)->count(),
                    ])
                    ->sortByDesc(fn (array $row): int => $row['sent'] + $row['failed'] + $row['queued'])
                    ->take(12)
                    ->all(),
                'top_failures' => $pushDeliveries
                    ->where('status', PushDelivery::STATUS_FAILED)
                    ->take(15)
                    ->map(fn (PushDelivery $delivery): array => [
                        'type' => $delivery->type,
                        'platform' => $delivery->platform,
                        'error' => $delivery->error_code ?: $delivery->error_message,
                        'when' => $delivery->failed_at?->format('d.m.Y H:i') ?: $delivery->created_at?->format('d.m.Y H:i'),
                    ])
                    ->values()
                    ->all(),
                'notifications_by_type' => $notifications
                    ->map(function (object $row): string {
                        $payload = json_decode((string) $row->data, true);

                        return (string) data_get($payload, 'type', 'unknown');
                    })
                    ->countBy()
                    ->sortDesc()
                    ->take(12)
                    ->all(),
            ],
            'system' => [
                'top_activity_events' => $activities
                    ->groupBy(fn (Activity $activity): string => trim(($activity->log_name ?: 'default').':'.($activity->event ?: 'event')))
                    ->map->count()
                    ->sortDesc()
                    ->take(15)
                    ->all(),
                'top_actors' => $activities
                    ->filter(fn (Activity $activity): bool => filled($activity->causer?->email) || filled($activity->causer?->name))
                    ->groupBy(fn (Activity $activity): string => $activity->causer?->full_name ?: $activity->causer?->name ?: $activity->causer?->email ?: 'system')
                    ->map->count()
                    ->sortDesc()
                    ->take(15)
                    ->all(),
                'failed_jobs' => $failedJobs->map(fn ($job): array => [
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'exception' => mb_substr((string) $job->exception, 0, 240),
                ])->all(),
                'media_by_collection' => $mediaCreated
                    ->groupBy(fn (Media $media): string => $media->collection_name ?: 'unknown')
                    ->map->count()
                    ->sortDesc()
                    ->take(12)
                    ->all(),
                'totals_now' => [
                    'users' => User::query()->count(),
                    'parishes' => Parish::query()->count(),
                    'news' => NewsPost::query()->count(),
                    'announcements' => AnnouncementSet::query()->count(),
                    'masses' => Mass::query()->count(),
                    'open_conversations' => OfficeConversation::query()->where('status', OfficeConversation::STATUS_OPEN)->count(),
                    'pushable_devices' => \App\Models\UserDevice::query()->pushable()->count(),
                    'pending_jobs' => DB::table('jobs')->count(),
                ],
            ],
        ];
    }

    private function mapRoleLabel(int $role): string
    {
        return match ($role) {
            2 => 'Superadmin',
            1 => 'Administrator parafii',
            default => 'Parafianin',
        };
    }
}
