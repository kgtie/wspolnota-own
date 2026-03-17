<?php

namespace App\Filament\SuperAdmin\Resources\PushDeliveries\Pages;

use App\Filament\SuperAdmin\Resources\PushDeliveries\PushDeliveryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPushDelivery extends ViewRecord
{
    protected static string $resource = PushDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
