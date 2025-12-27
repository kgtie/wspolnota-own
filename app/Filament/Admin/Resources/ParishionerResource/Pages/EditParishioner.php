<?php

namespace App\Filament\Admin\Resources\ParishionerResource\Pages;

use App\Filament\Admin\Resources\ParishionerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParishioner extends EditRecord
{
    protected static string $resource = ParishionerResource::class;

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
}
