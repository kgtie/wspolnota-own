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
        $deviceId = (string) $request->string('device_id');
        $pushToken = (string) $request->string('push_token');

        UserDevice::query()
            ->where('push_token', $pushToken)
            ->where(function ($query) use ($user, $deviceId): void {
                $query
                    ->where('user_id', '!=', $user->getKey())
                    ->orWhere('device_id', '!=', $deviceId);
            })
            ->delete();

        $existing = UserDevice::query()
            ->where('user_id', $user->getKey())
            ->where('device_id', $deviceId)
            ->first();

        $tokenChanged = ! $existing || $existing->push_token !== $pushToken;

        $device = UserDevice::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'device_id' => $deviceId,
            ],
            [
                'parish_id' => $request->integer('parish_id') ?: $user->home_parish_id,
                'provider' => (string) $request->string('provider'),
                'platform' => (string) $request->string('platform'),
                'push_token' => $pushToken,
                'device_name' => $request->input('device_name'),
                'app_version' => (string) $request->string('app_version'),
                'locale' => $request->input('locale'),
                'timezone' => $request->input('timezone'),
                'permission_status' => $request->input('permission_status', 'authorized'),
                'push_token_updated_at' => $tokenChanged ? now() : $existing?->push_token_updated_at,
                'last_seen_at' => now(),
                'disabled_at' => null,
            ],
        );

        return $this->success([
            'id' => (string) $device->getKey(),
            'provider' => $device->provider,
            'platform' => $device->platform,
            'permission_status' => $device->permission_status,
            'parish_id' => $device->parish_id ? (string) $device->parish_id : null,
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
