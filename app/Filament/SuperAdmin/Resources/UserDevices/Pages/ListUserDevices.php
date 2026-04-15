<?php

namespace App\Filament\SuperAdmin\Resources\UserDevices\Pages;

use App\Filament\SuperAdmin\Pages\FailedJobsCenter;
use App\Filament\SuperAdmin\Pages\FcmSettingsPage;
use App\Filament\SuperAdmin\Resources\PushDeliveries\PushDeliveryResource;
use App\Filament\SuperAdmin\Resources\UserDevices\UserDeviceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListUserDevices extends ListRecords
{
    protected static string $resource = UserDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_fcm_settings')
                ->label('FCM i push')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(FcmSettingsPage::getUrl()),
            Action::make('open_push_deliveries')
                ->label('Dostarczenia push')
                ->icon('heroicon-o-inbox-stack')
                ->url(PushDeliveryResource::getUrl()),
            Action::make('open_failed_jobs')
                ->label('Failed jobs')
                ->icon('heroicon-o-exclamation-triangle')
                ->url(FailedJobsCenter::getUrl()),
        ];
    }
}
