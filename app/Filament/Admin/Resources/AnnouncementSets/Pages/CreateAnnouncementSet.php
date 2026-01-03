<?php

namespace App\Filament\Admin\Resources\AnnouncementSets\Pages;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncementSet extends CreateRecord
{
    protected static string $resource = AnnouncementSetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['parish_id'] = \Filament\Facades\Filament::getTenant()->id;
        $data['created_by'] = auth()->id();
        $data['status'] = 'draft';

        return $data;
    }


}
