<?php

namespace App\Filament\Superadmin\Resources\UserResource\Pages;

use App\Filament\Superadmin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['verification_code'])) {
            $data['verification_code'] = $this->generateVerificationCode();
        }

        return $data;
    }

    private function generateVerificationCode(): string
    {
        do {
            $code = str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (\App\Models\User::where('verification_code', $code)->exists());

        return $code;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Użytkownik został utworzony';
    }
}
