<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\Me\UpdateMeRequest;
use App\Support\Api\UserPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        return $this->success([
            'user' => UserPayload::make($request->user()->fresh()),
        ]);
    }

    public function update(UpdateMeRequest $request): JsonResponse
    {
        $user = $request->user();

        $firstName = $request->input('first_name');
        $lastName = $request->input('last_name');

        if ($firstName !== null || $lastName !== null) {
            $current = preg_split('/\s+/', trim((string) $user->full_name)) ?: [];
            $resolvedFirst = $firstName ?? ($current[0] ?? $user->name);

            if ($lastName !== null) {
                $resolvedLast = $lastName;
            } else {
                $resolvedLast = count($current) > 1 ? implode(' ', array_slice($current, 1)) : '';
            }

            $user->full_name = trim("{$resolvedFirst} {$resolvedLast}");
        }

        if ($request->has('default_parish_id')) {
            $user->home_parish_id = $request->input('default_parish_id');
            $user->current_parish_id = $request->input('default_parish_id');
        }

        $user->save();

        return $this->success([
            'user' => UserPayload::make($user->fresh()),
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();

        $user->addMedia($validated['avatar'])->toMediaCollection('avatar');

        return $this->success([
            'avatar_url' => $user->fresh()->getFirstMediaUrl('avatar', 'thumb') ?: null,
        ], 201);
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->clearMediaCollection('avatar');

        return $this->noContent();
    }

    public function regenerateParishApprovalCode(Request $request): JsonResponse
    {
        $code = $request->user()->generateVerificationCode();

        return $this->success([
            'parish_approval_code' => $code,
        ]);
    }
}
