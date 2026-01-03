<?php

namespace App\Filament\Superadmin\Resources\AnnouncementSets\Pages;

use App\Filament\Superadmin\Resources\AnnouncementSets\AnnouncementSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncementSets extends ListRecords
{
    protected static string $resource = AnnouncementSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
