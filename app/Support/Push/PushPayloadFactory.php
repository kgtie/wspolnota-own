<?php

namespace App\Support\Push;

use App\Models\User;
use App\Models\UserDevice;
use App\Settings\FcmSettings;
use Illuminate\Notifications\DatabaseNotification;

class PushPayloadFactory
{
    public function __construct(private readonly FcmSettings $settings) {}

    public function makeFromDatabaseNotification(
        User $user,
        UserDevice $device,
        DatabaseNotification $notification,
    ): ?PushMessage {
        $data = is_array($notification->data) ? $notification->data : [];
        $type = (string) data_get($data, 'type', class_basename((string) $notification->type));
        $title = trim((string) data_get($data, 'title', ''));
        $body = trim((string) data_get($data, 'body', ''));

        if ($title === '' && $body === '') {
            return null;
        }

        $routingData = $this->normalizeRoutingData([
            'notification_id' => (string) $notification->getKey(),
            'type' => $type,
            ...((array) data_get($data, 'data', [])),
        ]);

        return new PushMessage(
            token: (string) $device->push_token,
            platform: (string) $device->platform,
            type: $type,
            title: $title,
            body: $body,
            data: $routingData,
            collapseKey: $this->resolveCollapseKey($type, $routingData),
            priority: $this->resolvePriority($type),
            ttlSeconds: $this->resolveTtlSeconds((string) $device->platform),
        );
    }

    /**
     * @param  array<string,mixed>  $routingData
     */
    public function makeTestMessage(
        string $token,
        string $platform,
        string $title,
        string $body,
        string $type,
        array $routingData = [],
    ): PushMessage {
        $normalizedData = $this->normalizeRoutingData([
            'notification_id' => 'test-'.now()->timestamp,
            'type' => $type,
            ...$routingData,
        ]);

        return new PushMessage(
            token: $token,
            platform: $platform,
            type: $type,
            title: $title,
            body: $body,
            data: $normalizedData,
            collapseKey: $this->resolveCollapseKey($type, $normalizedData),
            priority: $this->resolvePriority($type),
            ttlSeconds: $this->resolveTtlSeconds($platform),
        );
    }

    public function resolveTopic(string $type): ?string
    {
        return match ($type) {
            'NEWS_CREATED' => 'news',
            'ANNOUNCEMENTS_PACKAGE_PUBLISHED' => 'announcements',
            'MASS_PENDING' => 'mass_reminders',
            'OFFICE_MESSAGE_RECEIVED' => 'office_messages',
            'PARISH_APPROVAL_STATUS_CHANGED' => 'parish_approval_status',
            default => null,
        };
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,string>
     */
    private function normalizeRoutingData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $normalized[(string) $key] = $value ? 'true' : 'false';

                continue;
            }

            $normalized[(string) $key] = is_scalar($value)
                ? (string) $value
                : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        }

        return $normalized;
    }

    /**
     * @param  array<string,string>  $data
     */
    private function resolveCollapseKey(string $type, array $data): ?string
    {
        return match ($type) {
            'NEWS_CREATED' => $this->settings->news_collapsible
                ? 'news-'.($data['parish_id'] ?? 'global')
                : null,
            'ANNOUNCEMENTS_PACKAGE_PUBLISHED' => $this->settings->announcements_collapsible
                ? 'announcements-'.($data['parish_id'] ?? 'global')
                : null,
            'MASS_PENDING' => null,
            'OFFICE_MESSAGE_RECEIVED' => $this->settings->office_messages_collapsible
                ? 'office-'.($data['chat_id'] ?? 'global')
                : null,
            'PARISH_APPROVAL_STATUS_CHANGED' => $this->settings->parish_approval_collapsible
                ? 'parish-approval'
                : null,
            default => null,
        };
    }

    private function resolvePriority(string $type): string
    {
        return match ($type) {
            'NEWS_CREATED', 'ANNOUNCEMENTS_PACKAGE_PUBLISHED' => 'normal',
            default => 'high',
        };
    }

    private function resolveTtlSeconds(string $platform): int
    {
        return $platform === 'ios'
            ? max(60, (int) $this->settings->ios_ttl_seconds)
            : max(60, (int) $this->settings->android_ttl_seconds);
    }
}
