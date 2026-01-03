<?php

namespace App\Filament\Admin\Resources\AnnouncementSets\Pages;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncementSet extends CreateRecord
{
    protected static string $resource = AnnouncementSetResource::class;
}
