<?php

namespace App\Filament\SuperAdmin\Resources\UserDevices\Pages;

use App\Filament\SuperAdmin\Resources\Parishes\ParishResource;
use App\Filament\SuperAdmin\Resources\UserDevices\UserDeviceResource;
use App\Filament\SuperAdmin\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserDevice extends ViewRecord
{
    protected static string $resource = UserDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_user')
                ->label('Uzytkownik')
                ->icon('heroicon-o-user')
                ->visible(fn (): bool => $this->record->user !== null)
                ->url(fn (): ?string => $this->record->user ? UserResource::getUrl('view', ['record' => $this->record->user]) : null),
            Action::make('open_parish')
                ->label('Parafia')
                ->icon('heroicon-o-building-library')
                ->visible(fn (): bool => $this->record->parish !== null)
                ->url(fn (): ?string => $this->record->parish ? ParishResource::getUrl('view', ['record' => $this->record->parish]) : null),
            DeleteAction::make(),
        ];
    }
}
