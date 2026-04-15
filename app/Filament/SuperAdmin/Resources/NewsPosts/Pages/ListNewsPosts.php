<?php

namespace App\Filament\SuperAdmin\Resources\NewsPosts\Pages;

use App\Filament\SuperAdmin\Resources\NewsPosts\NewsPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNewsPosts extends ListRecords
{
    protected static string $resource = NewsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
