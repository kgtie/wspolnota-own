<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\Me\UpdateNotificationPreferencesRequest;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Obsługuje preferencje powiadomień i feed notyfikacji in-app użytkownika.
 */
class NotificationController extends ApiController
{
    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = $request->user();
        $existing = $user->notificationPreference;

        UserNotificationPreference::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            [
                'news_push' => (bool) data_get($request->input('news'), 'push'),
                'news_email' => (bool) data_get($request->input('news'), 'email'),
                'announcements_push' => (bool) data_get($request->input('announcements'), 'push'),
                'announcements_email' => (bool) data_get($request->input('announcements'), 'email'),
                'mass_reminders_push' => $request->has('mass_reminders')
                    ? (bool) data_get($request->input('mass_reminders'), 'push')
                    : (bool) ($existing?->mass_reminders_push ?? true),
                'mass_reminders_email' => $request->has('mass_reminders')
                    ? (bool) data_get($request->input('mass_reminders'), 'email')
                    : (bool) ($existing?->mass_reminders_email ?? true),
                'office_messages_push' => (bool) data_get($request->input('office_messages'), 'push'),
                'office_messages_email' => (bool) data_get($request->input('office_messages'), 'email'),
                'parish_approval_status_push' => (bool) data_get($request->input('parish_approval_status'), 'push'),
                'parish_approval_status_email' => (bool) data_get($request->input('parish_approval_status'), 'email'),
                'auth_security_push' => (bool) data_get($request->input('auth_security'), 'push'),
                'auth_security_email' => (bool) data_get($request->input('auth_security'), 'email'),
                'manual_messages_push' => $request->has('manual_messages')
                    ? (bool) data_get($request->input('manual_messages'), 'push')
                    : (bool) ($existing?->manual_messages_push ?? false),
                'manual_messages_email' => $request->has('manual_messages')
                    ? (bool) data_get($request->input('manual_messages'), 'email')
                    : (bool) ($existing?->manual_messages_email ?? true),
            ],
        );

        return $this->success([
            'updated' => true,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        [$cursorCreatedAt, $cursorId] = $this->decodeCursor($request->query('cursor'));

        $query = $request->user()->notifications()->getQuery();

        if ($cursorCreatedAt && $cursorId) {
            $query->where(function ($inner) use ($cursorCreatedAt, $cursorId): void {
                $inner->where('created_at', '<', $cursorCreatedAt)
                    ->orWhere(function ($sameTime) use ($cursorCreatedAt, $cursorId): void {
                        $sameTime->where('created_at', '=', $cursorCreatedAt)
                            ->where('id', '<', $cursorId);
                    });
            });
        }

        $limit = 20;

        $rows = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $items = $rows->take($limit)->values();

        $nextCursor = null;

        if ($hasMore && $items->isNotEmpty()) {
            $last = $items->last();
            $nextCursor = $this->encodeCursor(
                (string) optional($last->created_at)->toISOString(),
                (string) $last->id,
            );
        }

        return $this->collection(
            items: $items->map(fn (DatabaseNotification $notification) => $this->payload($notification))->all(),
            nextCursor: $nextCursor,
            hasMore: $hasMore,
        );
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return $this->success([
            'id' => (string) $notification->id,
            'read_at' => optional($notification->fresh()->read_at)?->toISOString(),
        ]);
    }

    private function payload(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => (string) $notification->id,
            'type' => (string) data_get($data, 'type', Str::upper(Str::snake(class_basename((string) $notification->type)))),
            'title' => (string) data_get($data, 'title', ''),
            'body' => (string) data_get($data, 'body', ''),
            'data' => (array) data_get($data, 'data', []),
            'read_at' => optional($notification->read_at)?->toISOString(),
            'created_at' => optional($notification->created_at)?->toISOString(),
        ];
    }

    private function encodeCursor(string $createdAtIso, string $id): string
    {
        return rtrim(strtr(base64_encode(json_encode([
            'created_at' => $createdAtIso,
            'id' => $id,
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    private function decodeCursor(mixed $cursor): array
    {
        if (! is_string($cursor) || $cursor === '') {
            return [null, null];
        }

        $padding = strlen($cursor) % 4;
        if ($padding > 0) {
            $cursor .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);

        if (! is_string($decoded) || $decoded === '') {
            return [null, null];
        }

        $payload = json_decode($decoded, true);

        if (! is_array($payload)) {
            return [null, null];
        }

        return [
            data_get($payload, 'created_at'),
            data_get($payload, 'id'),
        ];
    }
}
