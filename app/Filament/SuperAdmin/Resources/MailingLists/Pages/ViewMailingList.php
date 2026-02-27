<?php

namespace App\Filament\SuperAdmin\Resources\MailingLists\Pages;

use App\Filament\SuperAdmin\Resources\MailingLists\MailingListResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMailingList extends ViewRecord
{
    protected static string $resource = MailingListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
