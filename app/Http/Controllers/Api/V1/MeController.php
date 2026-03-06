<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Requests\Api\Me\UpdateEmailRequest;
use App\Http\Requests\Api\Me\UpdateMeRequest;
use App\Http\Requests\Api\Me\UpdatePasswordRequest;
use App\Notifications\ApiVerifyEmailNotification;
use App\Services\Auth\MobileTokenService;
use App\Support\Api\ErrorCode;
use App\Support\Api\UserPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MeController extends ApiController
{
    public function __construct(private readonly MobileTokenService $tokenService) {}

    public function show(Request $request): JsonResponse
    {
        return $this->success([
            'user' => UserPayload::make($request->user()->fresh()),
        ]);
    }

    public function update(UpdateMeRequest $request): JsonResponse
    {
        $user = $request->user();
        $defaultParishChanged = false;

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
            $newDefaultParishId = $request->filled('default_parish_id')
                ? (int) $request->input('default_parish_id')
                : null;
            $currentDefaultParishId = $user->home_parish_id ? (int) $user->home_parish_id : null;

            if ($currentDefaultParishId !== $newDefaultParishId) {
                $defaultParishChanged = true;
                $user->home_parish_id = $newDefaultParishId;
                $user->resetParishApproval();
            }
        }

        $user->save();

        if ($defaultParishChanged && $user->home_parish_id) {
            $user->generateVerificationCode();
        }

        return $this->success([
            'user' => UserPayload::make($user->fresh()),
        ]);
    }

    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check((string) $request->string('current_password'), (string) $user->password)) {
            throw new ApiException(ErrorCode::AUTH_PASSWORD_INVALID, 'Nieprawidłowe hasło.', 401);
        }

        $email = mb_strtolower(trim((string) $request->string('email')));
        $emailChanged = mb_strtolower((string) $user->email) !== $email;

        if ($emailChanged) {
            $user->forceFill([
                'email' => $email,
                'email_verified_at' => null,
            ])->save();

            $user->notify(new ApiVerifyEmailNotification);
        }

        return $this->success([
            'status' => $emailChanged ? 'EMAIL_UPDATED_VERIFICATION_REQUIRED' : 'EMAIL_UNCHANGED',
            'user' => UserPayload::make($user->fresh()),
            'requires_email_verification' => ! $user->fresh()->hasVerifiedEmail(),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check((string) $request->string('current_password'), (string) $user->password)) {
            throw new ApiException(ErrorCode::AUTH_PASSWORD_INVALID, 'Nieprawidłowe hasło.', 401);
        }

        $user->forceFill([
            'password' => (string) $request->string('password'),
            'remember_token' => Str::random(60),
        ])->save();

        $this->tokenService->revokeAllForUser($user);

        return $this->success([
            'status' => 'PASSWORD_CHANGED',
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
        if (! $request->user()->home_parish_id) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Najpierw ustaw domyślną parafię.', 403);
        }

        $code = $request->user()->generateVerificationCode();

        return $this->success([
            'parish_approval_code' => $code,
        ]);
    }
}
