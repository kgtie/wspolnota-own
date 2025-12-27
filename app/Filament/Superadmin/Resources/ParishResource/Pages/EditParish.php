<?php

namespace App\Filament\Superadmin\Resources\ParishResource\Pages;

use App\Filament\Superadmin\Resources\ParishResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParish extends EditRecord
{
    protected static string $resource = ParishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Podgląd'),
            Actions\DeleteAction::make()
                ->label('Usuń'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Parafia została zaktualizowana';
    }
}
