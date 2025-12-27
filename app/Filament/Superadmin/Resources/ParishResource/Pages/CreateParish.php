<?php

namespace App\Filament\Superadmin\Resources\ParishResource\Pages;

use App\Filament\Superadmin\Resources\ParishResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateParish extends CreateRecord
{
    protected static string $resource = ParishResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug']) && !empty($data['short_name'])) {
            $data['slug'] = Str::slug($data['short_name']);
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Parafia została utworzona';
    }
}
