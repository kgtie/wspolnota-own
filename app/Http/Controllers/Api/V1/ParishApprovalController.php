<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Requests\Api\ParishApprovals\ApproveParishApprovalRequest;
use App\Http\Requests\Api\ParishApprovals\PendingParishApprovalsRequest;
use App\Models\Parish;
use App\Models\User;
use App\Support\Api\ApiAudit;
use App\Support\Api\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParishApprovalController extends ApiController
{
    /**
     * Odczytuje parafianina po 9-cyfrowym kodzie. Administrator parafii nie może
     * tym endpointem enumerować parafian z cudzych parafii.
     */
    public function byCode(Request $request, string $code): JsonResponse
    {
        $operator = $this->approvalOperator($request);

        $user = User::query()
            ->with('homeParish')
            ->where('verification_code', $code)
            ->where('status', 'active')
            ->first();

        if (! $user instanceof User) {
            throw new ApiException(ErrorCode::NOT_FOUND, 'Nie znaleziono użytkownika dla podanego kodu.', 404);
        }

        if (! $operator->isSuperAdmin() && ! $this->operatorCanManageParish($operator, (int) $user->home_parish_id)) {
            throw new ApiException(ErrorCode::NOT_FOUND, 'Nie znaleziono użytkownika dla podanego kodu.', 404);
        }

        ApiAudit::log(
            logName: 'api-parish-approvals',
            event: 'api_parish_approval_lookup_succeeded',
            message: 'Operator odczytał użytkownika po kodzie zatwierdzenia parafialnego.',
            causer: $operator,
            subject: $user,
            properties: [
                'home_parish_id' => $user->home_parish_id,
                'can_operator_approve' => $this->canOperatorApprove($operator, $user),
            ],
        );

        return $this->success([
            'user' => $this->approvalLookupPayload($user, $operator),
        ]);
    }

    /**
     * Zatwierdza użytkownika kodem parafialnym lub zeskanowanym odpowiednikiem QR.
     */
    public function approve(ApproveParishApprovalRequest $request, int $userId): JsonResponse
    {
        $operator = $this->approvalOperator($request);
        $parish = $this->activeParishOrFail((int) $request->integer('parish_id'));

        if (! $operator->isSuperAdmin() && ! $this->operatorCanManageParish($operator, (int) $parish->getKey())) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Nie możesz zatwierdzać parafian tej parafii.', 403);
        }

        $user = User::query()
            ->with('homeParish')
            ->where('status', 'active')
            ->find($userId);

        if (! $user instanceof User) {
            throw new ApiException(ErrorCode::NOT_FOUND, 'Nie znaleziono użytkownika do zatwierdzenia.', 404);
        }

        if ((int) $user->home_parish_id !== (int) $parish->getKey()) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Użytkownik nie należy do wskazanej parafii.', 403);
        }

        if ((bool) $user->is_user_verified) {
            throw new ApiException(ErrorCode::CONFLICT, 'Użytkownik jest już zatwierdzony.', 409);
        }

        $providedCode = preg_replace('/\D+/', '', (string) $request->string('approval_code')) ?? '';
        $expectedCode = (string) ($user->verification_code ?? '');

        if ($providedCode !== $expectedCode) {
            ApiAudit::log(
                logName: 'api-parish-approvals',
                event: 'api_parish_approval_failed_invalid_code',
                message: 'Operator podał nieprawidłowy kod podczas zatwierdzania parafianina przez API.',
                causer: $operator,
                subject: $user,
                properties: [
                    'parish_id' => $parish->getKey(),
                    'expected_code_exists' => $expectedCode !== '',
                    'provided_code_length' => strlen($providedCode),
                ],
            );

            throw ValidationException::withMessages([
                'approval_code' => ['Podany kod zatwierdzenia jest nieprawidłowy.'],
            ]);
        }

        DB::transaction(function () use ($user, $operator): void {
            $user->verify($operator);

            activity('api-parish-approvals')
                ->causedBy($operator)
                ->performedOn($user)
                ->event('user_verified_by_code_api')
                ->withProperties([
                    'verification_method' => '9_digit_code_or_qr',
                    'parish_id' => $user->home_parish_id,
                    'verified_user_email' => $user->email,
                ])
                ->log('Użytkownik został zatwierdzony przez API kodem parafialnym.');
        });

        return $this->success([
            'status' => 'PARISHIONER_APPROVED',
            'user' => $this->approvalLookupPayload($user->fresh(['homeParish']), $operator),
        ]);
    }

    /**
     * Zwraca listę oczekujących parafian z prostym live search dla paneli mobile.
     */
    public function pending(PendingParishApprovalsRequest $request): JsonResponse
    {
        $operator = $this->approvalOperator($request);
        $parish = $this->activeParishOrFail((int) $request->integer('parish_id'));

        if (! $operator->isSuperAdmin() && ! $this->operatorCanManageParish($operator, (int) $parish->getKey())) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Nie możesz przeglądać oczekujących parafian tej parafii.', 403);
        }

        $search = trim((string) $request->string('search'));

        $query = User::query()
            ->with('homeParish')
            ->where('status', 'active')
            ->where('home_parish_id', $parish->getKey())
            ->where('is_user_verified', false)
            ->orderByDesc('created_at')
            ->limit(50);

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->get();

        return $this->success([
            'parish_id' => (string) $parish->getKey(),
            'parish_name' => $parish->short_name ?: $parish->name,
            'items' => $users->map(fn (User $user): array => $this->pendingPayload($user))->values()->all(),
        ]);
    }

    private function approvalOperator(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Ten endpoint jest dostępny tylko dla administratora parafii lub superadministratora.', 403);
        }

        return $user;
    }

    private function operatorCanManageParish(User $operator, int $parishId): bool
    {
        if ($parishId <= 0) {
            return false;
        }

        if ($operator->isSuperAdmin()) {
            return true;
        }

        return $operator->managedParishes()
            ->wherePivot('is_active', true)
            ->whereKey($parishId)
            ->exists();
    }

    private function approvalLookupPayload(User $user, User $operator): array
    {
        [$firstName, $lastName] = $this->extractNameParts($user);

        return [
            'id' => (string) $user->getKey(),
            'login' => (string) $user->name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => (string) $user->email,
            'avatar_url' => $user->avatar_media_url,
            'default_parish_id' => $user->home_parish_id ? (string) $user->home_parish_id : null,
            'default_parish_name' => $user->homeParish?->short_name ?: $user->homeParish?->name,
            'is_email_verified' => $user->hasVerifiedEmail(),
            'is_parish_approved' => (bool) $user->is_user_verified,
            'parish_approval_code' => $user->verification_code,
            'can_operator_approve' => $this->canOperatorApprove($operator, $user),
        ];
    }

    private function pendingPayload(User $user): array
    {
        [$firstName, $lastName] = $this->extractNameParts($user);

        return [
            'id' => (string) $user->getKey(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'login' => (string) $user->name,
            'email' => (string) $user->email,
            'avatar_url' => $user->avatar_media_url,
            'default_parish_id' => $user->home_parish_id ? (string) $user->home_parish_id : null,
            'default_parish_name' => $user->homeParish?->short_name ?: $user->homeParish?->name,
            'created_at' => optional($user->created_at)?->toISOString(),
            'is_email_verified' => $user->hasVerifiedEmail(),
            'is_parish_approved' => (bool) $user->is_user_verified,
        ];
    }

    private function canOperatorApprove(User $operator, User $user): bool
    {
        if ((bool) $user->is_user_verified) {
            return false;
        }

        if (! $user->home_parish_id) {
            return false;
        }

        return $this->operatorCanManageParish($operator, (int) $user->home_parish_id);
    }

    private function extractNameParts(User $user): array
    {
        $full = trim((string) $user->full_name);

        if ($full === '') {
            return [(string) $user->name, ''];
        }

        $parts = preg_split('/\s+/', $full) ?: [];
        $firstName = array_shift($parts) ?: '';
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
    }
}
