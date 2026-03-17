<?php

namespace App\Support\SuperAdmin;

use App\Jobs\SendManualPushToDeviceJob;
use App\Mail\CommunicationBroadcastMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class InstantCommunicationService
{
    /**
     * @param  Collection<int,User>|array<int,User>  $users
     * @return array{users:int, queued:int, skipped:int}
     */
    public function sendEmailToUsers(Collection|array $users, string $subjectLine, string $messageBody, ?User $actor = null): array
    {
        $rows = collect($users)
            ->filter(fn ($user): bool => $user instanceof User)
            ->filter(fn (User $user): bool => filled($user->email))
            ->unique(fn (User $user): string => mb_strtolower((string) $user->email))
            ->values();

        $queued = 0;

        foreach ($rows as $user) {
            Mail::to((string) $user->email)->queue(new CommunicationBroadcastMessage(
                subjectLine: $subjectLine,
                messageBody: $messageBody,
                senderName: $actor?->full_name ?: $actor?->name,
                senderEmail: $actor?->email,
            ));

            $queued++;
        }

        return [
            'users' => $rows->count(),
            'queued' => $queued,
            'skipped' => max(0, collect($users)->count() - $rows->count()),
        ];
    }

    /**
     * @param  Collection<int,User>|array<int,User>  $users
     * @param  array<string,mixed>  $routingData
     * @return array{users:int, devices:int, skipped:int}
     */
    public function queuePushToUsers(
        Collection|array $users,
        string $title,
        string $body,
        string $type = 'MANUAL_MESSAGE',
        array $routingData = [],
    ): array {
        $rows = collect($users)
            ->filter(fn ($user): bool => $user instanceof User)
            ->unique(fn (User $user): int|string => $user->getKey())
            ->values();

        $devicesQueued = 0;
        $targetUsers = 0;

        foreach ($rows as $user) {
            $devices = $user->relationLoaded('devices')
                ? $user->devices->filter(fn ($device) => $device->disabled_at === null && in_array($device->permission_status, ['authorized', 'provisional'], true) && filled($device->push_token))
                : $user->devices()->pushable()->get();

            if ($devices->isEmpty()) {
                continue;
            }

            $targetUsers++;

            foreach ($devices as $device) {
                SendManualPushToDeviceJob::dispatch(
                    deviceId: (int) $device->getKey(),
                    userId: (int) $user->getKey(),
                    title: $title,
                    body: $body,
                    type: $type,
                    routingData: $routingData,
                );

                $devicesQueued++;
            }
        }

        return [
            'users' => $targetUsers,
            'devices' => $devicesQueued,
            'skipped' => max(0, $rows->count() - $targetUsers),
        ];
    }
}
