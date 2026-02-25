<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenantId = Filament::getTenant()?->getKey();

        $data['role'] = 0;
        $data['status'] = $data['status'] ?? 'active';
        $data['password'] = Str::password(32);
        $data['verification_code'] = filled($data['verification_code'] ?? null)
            ? $data['verification_code']
            : UserResource::generateUniqueVerificationCode();

        if ($tenantId) {
            $data['home_parish_id'] = $tenantId;
            $data['current_parish_id'] = $data['current_parish_id'] ?? $tenantId;
        }

        if ($data['is_user_verified'] ?? false) {
            $verifiedBy = Filament::auth()->user();

            $data['user_verified_at'] = now();
            $data['verified_by_user_id'] = $verifiedBy instanceof User ? $verifiedBy->id : null;
        } else {
            $data['user_verified_at'] = null;
            $data['verified_by_user_id'] = null;
        }

        return $data;
    }
}
