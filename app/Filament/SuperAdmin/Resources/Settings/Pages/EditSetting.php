<?php

namespace App\Filament\SuperAdmin\Resources\Settings\Pages;

use App\Filament\SuperAdmin\Resources\Settings\SettingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['group'] = trim((string) ($data['group'] ?? ''));
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['payload'] = SettingResource::normalizePayload((string) ($data['payload'] ?? ''));

        return $data;
    }
}
