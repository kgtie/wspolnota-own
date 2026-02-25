<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs\Pages;

use App\Filament\SuperAdmin\Resources\ActivityLogs\ActivityLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
