<?php

namespace App\Support\Push;

use App\Contracts\PushSender;
use App\Models\PushDelivery;
use App\Models\User;
use App\Models\UserDevice;
use App\Settings\FcmSettings;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Notifications\DatabaseNotification;

class PushDispatchService
{
    public function __construct(
        private readonly PushSender $sender,
        private readonly PushPayloadFactory $payloadFactory,
        private readonly NotificationPreferenceResolver $preferences,
        private readonly FcmSettings $settings,
    ) {}

    public function dispatchDatabaseNotification(User $user, DatabaseNotification $notification): int
    {
        if (! $this->settings->enabled) {
            return 0;
        }

        $topic = $this->payloadFactory->resolveTopic(
            (string) data_get($notification->data, 'type', ''),
        );

        if (! $topic || ! $this->preferences->wantsPush($user, $topic)) {
            return 0;
        }

        $devices = $user->devices()
            ->pushable()
            ->get();

        $attempts = 0;

        foreach ($devices as $device) {
            if (! $this->deviceMatchesNotificationContext($device, $notification)) {
                continue;
            }

            $message = $this->payloadFactory->makeFromDatabaseNotification($user, $device, $notification);

            if (! $message) {
                continue;
            }

            $delivery = PushDelivery::query()->create([
                'user_id' => $user->getKey(),
                'user_device_id' => $device->getKey(),
                'notification_id' => (string) $notification->getKey(),
                'provider' => 'fcm',
                'platform' => $device->platform,
                'type' => $message->type,
                'status' => PushDelivery::STATUS_QUEUED,
                'collapse_key' => $message->collapseKey,
                'payload' => [
                    'title' => $message->title,
                    'body' => $message->body,
                    'data' => $message->data,
                ],
            ]);

            $result = $this->sender->send($message);
            $attempts++;

            if ($result->successful) {
                $delivery->forceFill([
                    'status' => PushDelivery::STATUS_SENT,
                    'message_id' => $result->messageId,
                    'response' => $result->response,
                    'sent_at' => now(),
                    'failed_at' => null,
                    'error_code' => null,
                    'error_message' => null,
                ])->save();

                $device->markPushSent();

                continue;
            }

            $delivery->forceFill([
                'status' => PushDelivery::STATUS_FAILED,
                'response' => $result->response,
                'failed_at' => now(),
                'error_code' => $result->errorCode,
                'error_message' => $result->errorMessage,
            ])->save();

            $device->markPushFailed(
                error: trim(implode(' | ', array_filter([$result->errorCode, $result->errorMessage]))),
                disable: $result->shouldDisableDevice,
            );
        }

        return $attempts;
    }

    /**
     * @param  array<string,mixed>  $routingData
     */
    public function sendTestPush(
        string $token,
        string $platform,
        string $title,
        string $body,
        string $type,
        array $routingData = [],
        ?UserDevice $device = null,
        ?User $user = null,
    ): PushDelivery {
        $message = $this->payloadFactory->makeTestMessage(
            token: $token,
            platform: $platform,
            title: $title,
            body: $body,
            type: $type,
            routingData: $routingData,
        );

        $delivery = PushDelivery::query()->create([
            'user_id' => $user?->getKey(),
            'user_device_id' => $device?->getKey(),
            'notification_id' => null,
            'provider' => 'fcm',
            'platform' => $platform,
            'type' => $type,
            'status' => PushDelivery::STATUS_QUEUED,
            'collapse_key' => $message->collapseKey,
            'payload' => [
                'title' => $message->title,
                'body' => $message->body,
                'data' => $message->data,
            ],
        ]);

        $result = $this->sender->send($message);

        if ($result->successful) {
            $delivery->forceFill([
                'status' => PushDelivery::STATUS_SENT,
                'message_id' => $result->messageId,
                'response' => $result->response,
                'sent_at' => now(),
            ])->save();

            $device?->markPushSent();

            return $delivery;
        }

        $delivery->forceFill([
            'status' => PushDelivery::STATUS_FAILED,
            'response' => $result->response,
            'failed_at' => now(),
            'error_code' => $result->errorCode,
            'error_message' => $result->errorMessage,
        ])->save();

        if ($device) {
            $device->markPushFailed(
                error: trim(implode(' | ', array_filter([$result->errorCode, $result->errorMessage]))),
                disable: $result->shouldDisableDevice,
            );
        }

        return $delivery;
    }

    private function deviceMatchesNotificationContext(UserDevice $device, DatabaseNotification $notification): bool
    {
        $notificationParishId = data_get($notification->data, 'data.parish_id');

        if (! $device->parish_id || ! $notificationParishId) {
            return true;
        }

        return (string) $device->parish_id === (string) $notificationParishId;
    }
}
