<?php

namespace App\Filament\Superadmin\Resources\Parishes\ParishResource\Pages;

use App\Filament\Superadmin\Resources\Parishes\ParishResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditParish extends EditRecord
{
    protected static string $resource = ParishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
