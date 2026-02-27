<?php

namespace App\Filament\SuperAdmin\Resources\Parishes\Pages;

use App\Filament\SuperAdmin\Resources\Parishes\ParishResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParishes extends ListRecords
{
    protected static string $resource = ParishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
