<?php

namespace App\Filament\SuperAdmin\Resources\Parishes\Pages;

use App\Filament\SuperAdmin\Resources\Parishes\ParishResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewParish extends ViewRecord
{
    protected static string $resource = ParishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
