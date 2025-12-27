<?php

namespace App\Filament\Superadmin\Resources\ParishResource\Pages;

use App\Filament\Superadmin\Resources\ParishResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParishes extends ListRecords
{
    protected static string $resource = ParishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Dodaj parafię')
                ->icon('heroicon-o-plus'),
        ];
    }
}
