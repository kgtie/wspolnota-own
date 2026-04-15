<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserDevice;
use App\Support\Push\PushDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendManualPushToDeviceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string,mixed>  $routingData
     */
    public function __construct(
        public readonly int $deviceId,
        public readonly ?int $userId,
        public readonly string $title,
        public readonly string $body,
        public readonly string $type = 'MANUAL_MESSAGE',
        public readonly array $routingData = [],
    ) {}

    public function handle(PushDispatchService $dispatcher): void
    {
        $device = UserDevice::query()->with('user')->find($this->deviceId);

        if (! $device instanceof UserDevice || $device->disabled_at !== null) {
            return;
        }

        $user = $device->user_id ? User::query()->find($this->userId ?: $device->user_id) : null;

        $dispatcher->sendTestPush(
            token: (string) $device->push_token,
            platform: (string) $device->platform,
            title: $this->title,
            body: $this->body,
            type: $this->type,
            routingData: $this->routingData,
            device: $device,
            user: $user instanceof User ? $user : null,
        );
    }
}
