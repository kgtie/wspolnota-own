<?php

namespace App\Filament\SuperAdmin\Resources\UserDevices\Pages;

use App\Filament\SuperAdmin\Resources\UserDevices\UserDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserDevice extends ViewRecord
{
    protected static string $resource = UserDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
