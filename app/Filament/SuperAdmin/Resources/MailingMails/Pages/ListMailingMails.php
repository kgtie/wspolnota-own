<?php

namespace App\Filament\SuperAdmin\Resources\MailingMails\Pages;

use App\Filament\SuperAdmin\Resources\MailingMails\MailingMailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMailingMails extends ListRecords
{
    protected static string $resource = MailingMailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
