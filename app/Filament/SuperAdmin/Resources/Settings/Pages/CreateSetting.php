<?php

namespace App\Filament\SuperAdmin\Resources\Settings\Pages;

use App\Filament\SuperAdmin\Resources\Settings\SettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['group'] = trim((string) ($data['group'] ?? ''));
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['payload'] = SettingResource::normalizePayload((string) ($data['payload'] ?? ''));

        return $data;
    }
}
