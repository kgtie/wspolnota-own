<?php

namespace App\Filament\Superadmin\Resources\AnnouncementSets\Pages;

use App\Filament\Superadmin\Resources\AnnouncementSets\AnnouncementSetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncementSet extends CreateRecord
{
    protected static string $resource = AnnouncementSetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }
}
