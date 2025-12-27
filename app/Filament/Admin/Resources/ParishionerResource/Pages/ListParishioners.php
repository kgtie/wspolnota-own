<?php

namespace App\Filament\Admin\Resources\ParishionerResource\Pages;

use App\Filament\Admin\Resources\ParishionerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParishioners extends ListRecords
{
    protected static string $resource = ParishionerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Dodaj parafianina'),
        ];
    }
}
