<?php

namespace App\Filament\SuperAdmin\Resources\Parishes\Pages;

use App\Filament\SuperAdmin\Resources\Parishes\ParishResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditParish extends EditRecord
{
    protected static string $resource = ParishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
