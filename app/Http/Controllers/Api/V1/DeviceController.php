<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Requests\Api\Me\StoreDeviceRequest;
use App\Models\UserDevice;
use App\Support\Api\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends ApiController
{
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $user = $request->user();

        $device = UserDevice::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'device_id' => (string) $request->string('device_id'),
            ],
            [
                'provider' => (string) $request->string('provider'),
                'platform' => (string) $request->string('platform'),
                'push_token' => (string) $request->string('push_token'),
                'device_name' => $request->input('device_name'),
                'app_version' => (string) $request->string('app_version'),
                'locale' => $request->input('locale'),
                'timezone' => $request->input('timezone'),
                'last_seen_at' => now(),
            ],
        );

        return $this->success([
            'id' => (string) $device->getKey(),
            'provider' => $device->provider,
            'platform' => $device->platform,
            'app_version' => $device->app_version,
            'created_at' => optional($device->created_at)?->toISOString(),
            'updated_at' => optional($device->updated_at)?->toISOString(),
        ]);
    }

    public function destroy(Request $request, int $deviceId): JsonResponse
    {
        $device = UserDevice::query()
            ->where('user_id', $request->user()->getKey())
            ->find($deviceId);

        if (! $device) {
            throw new ApiException(ErrorCode::DEVICE_NOT_FOUND, 'Nie znaleziono urządzenia.', 404);
        }

        $device->delete();

        return $this->noContent();
    }
}
