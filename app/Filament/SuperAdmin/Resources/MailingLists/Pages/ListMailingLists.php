<?php

namespace App\Filament\SuperAdmin\Resources\MailingLists\Pages;

use App\Filament\SuperAdmin\Resources\MailingLists\MailingListResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMailingLists extends ListRecords
{
    protected static string $resource = MailingListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
