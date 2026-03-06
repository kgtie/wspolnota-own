<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\LogoutAllRequest;
use App\Http\Requests\Api\Auth\RefreshRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ApiResetPasswordNotification;
use App\Notifications\ApiVerifyEmailNotification;
use App\Services\Auth\MobileTokenService;
use App\Support\Api\ErrorCode;
use App\Support\Api\UserPayload;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends ApiController
{
    public function __construct(private readonly MobileTokenService $tokenService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $firstName = trim((string) $request->string('first_name'));
        $lastName = trim((string) $request->string('last_name'));

        $user = User::query()->create([
            'name' => $this->generateUsernameFromEmail((string) $request->string('email')),
            'full_name' => trim("{$firstName} {$lastName}"),
            'email' => (string) $request->string('email'),
            'password' => (string) $request->string('password'),
            'home_parish_id' => $request->input('default_parish_id'),
            'role' => 0,
            'status' => 'active',
        ]);

        if ($user->home_parish_id) {
            $user->generateVerificationCode();
        }

        $user->notify(new ApiVerifyEmailNotification);

        $tokens = $this->tokenService->issuePair($user, $request);

        $freshUser = $user->fresh();

        return $this->success([
            'user' => UserPayload::make($freshUser),
            'tokens' => $this->tokensPayload($tokens),
            'access_level' => $this->resolveAccessLevel($freshUser),
            'requires_email_verification' => ! $freshUser->hasVerifiedEmail(),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = (string) $request->string('login');
        $user = $this->findUserByLogin($login);

        if (! $user || ! Hash::check((string) $request->string('password'), (string) $user->password)) {
            throw new ApiException(ErrorCode::AUTH_INVALID_CREDENTIALS, 'Nieprawidłowy login lub hasło.', 401);
        }

        if ($user->status !== 'active') {
            throw new ApiException(ErrorCode::AUTH_ACCOUNT_LOCKED, 'Konto jest zablokowane lub nieaktywne.', 423);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $tokens = $this->tokenService->issuePair($user, $request);

        return $this->success([
            'user' => UserPayload::make($user->fresh()),
            'tokens' => $this->tokensPayload($tokens),
            'access_level' => $this->resolveAccessLevel($user),
            'requires_email_verification' => ! $user->hasVerifiedEmail(),
        ]);
    }

    public function sendVerificationNotification(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success([
                'status' => 'EMAIL_ALREADY_VERIFIED',
                'user' => UserPayload::make($user->fresh()),
            ]);
        }

        $user->notify(new ApiVerifyEmailNotification);

        return $this->success([
            'status' => 'EMAIL_VERIFICATION_SENT',
            'user' => UserPayload::make($user->fresh()),
        ]);
    }

    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        if (! $request->hasValidSignature()) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Link weryfikacyjny jest nieprawidłowy lub wygasł.', 403);
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Link weryfikacyjny jest nieprawidłowy.', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success([
                'status' => 'EMAIL_ALREADY_VERIFIED',
                'user' => UserPayload::make($user->fresh()),
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->success([
            'status' => 'EMAIL_VERIFIED',
            'user' => UserPayload::make($user->fresh()),
        ]);
    }

    public function refresh(RefreshRequest $request): JsonResponse
    {
        $result = $this->tokenService->rotateByRefreshToken((string) $request->string('refresh_token'), $request);

        /** @var User $user */
        $user = $result['user'];

        return $this->success([
            'user' => UserPayload::make($user->fresh()),
            'tokens' => $this->tokensPayload($result['tokens']),
            'access_level' => $this->resolveAccessLevel($user),
            'requires_email_verification' => ! $user->hasVerifiedEmail(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $accessTokenId = $request->attributes->get('api_access_token_id');

        if (is_int($accessTokenId) || ctype_digit((string) $accessTokenId)) {
            $this->tokenService->revokeSessionByAccessTokenId((int) $accessTokenId);
        }

        $refreshToken = (string) $request->input('refresh_token', '');

        if ($refreshToken !== '' && $request->user()) {
            $this->tokenService->revokeRefreshTokenForUser($refreshToken, $request->user());
        }

        return $this->success([
            'status' => 'LOGGED_OUT',
        ]);
    }

    public function logoutAll(LogoutAllRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! Hash::check((string) $request->string('password'), (string) $user->password)) {
            throw new ApiException(ErrorCode::AUTH_PASSWORD_INVALID, 'Nieprawidłowe hasło.', 401);
        }

        $this->tokenService->revokeAllForUser($user);

        return $this->success([
            'status' => 'LOGGED_OUT_ALL',
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', (string) $request->string('email'))
            ->first();

        if ($user) {
            $token = Password::broker()->createToken($user);
            $user->notify(new ApiResetPasswordNotification($token));
        }

        return $this->success([
            'status' => 'PASSWORD_RESET_EMAIL_SENT_IF_EXISTS',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $this->tokenService->revokeAllForUser($user);
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success([
                'status' => 'PASSWORD_RESET',
            ]);
        }

        if ($status === Password::INVALID_TOKEN) {
            throw new ApiException(ErrorCode::AUTH_RESET_TOKEN_INVALID, 'Token resetu hasła jest nieprawidłowy.', 400);
        }

        if ($status === Password::INVALID_USER) {
            throw new ApiException(ErrorCode::AUTH_RESET_TOKEN_INVALID, 'Nieprawidłowe dane resetu hasła.', 400);
        }

        throw new ApiException(ErrorCode::AUTH_RESET_TOKEN_EXPIRED, 'Token resetu hasła wygasł.', 400);
    }

    private function findUserByLogin(string $login): ?User
    {
        $query = User::query();

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return $query->where('email', $login)->first();
        }

        return $query->where('name', $login)
            ->orWhere('email', $login)
            ->first();
    }

    private function generateUsernameFromEmail(string $email): string
    {
        $base = Str::lower(Str::slug(Str::before($email, '@'), separator: '.'));
        $base = $base !== '' ? Str::replace('-', '.', $base) : 'user';
        $candidate = $base;

        while (User::query()->where('name', $candidate)->exists()) {
            $candidate = $base.'.'.random_int(100, 999);
        }

        return $candidate;
    }

    private function resolveAccessLevel(User $user): string
    {
        if (! $user->hasVerifiedEmail()) {
            return 'AUTHENTICATED_LIMITED';
        }

        if (! $user->is_user_verified) {
            return 'AUTHENTICATED';
        }

        return 'PARISH_APPROVED';
    }

    private function tokensPayload(array $issued): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $issued['access_token'],
            'access_expires_in' => $issued['access_expires_in'],
            'refresh_token' => $issued['refresh_token'],
            'refresh_expires_in' => $issued['refresh_expires_in'],
        ];
    }
}
