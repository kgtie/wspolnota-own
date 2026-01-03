<?php

namespace App\Filament\Superadmin\Resources\AnnouncementSets\Pages;

use App\Filament\Superadmin\Resources\AnnouncementSets\AnnouncementSetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncementSet extends EditRecord
{
    protected static string $resource = AnnouncementSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
