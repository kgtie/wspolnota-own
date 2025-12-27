<?php

namespace App\Filament\Admin\Resources\ParishionerResource\Pages;

use App\Filament\Admin\Resources\ParishionerResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateParishioner extends CreateRecord
{
    protected static string $resource = ParishionerResource::class;

    /**
     * Automatycznie ustawiamy home_parish_id na aktualną parafię
     * oraz generujemy kod weryfikacyjny
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $parish = Filament::getTenant();

        $data['home_parish_id'] = $parish?->id;
        $data['role'] = 0; // Zawsze zwykły użytkownik
        $data['verification_code'] = ParishionerResource::generateVerificationCode();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
