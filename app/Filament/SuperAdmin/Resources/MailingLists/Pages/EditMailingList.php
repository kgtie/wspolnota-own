<?php

namespace App\Filament\SuperAdmin\Resources\MailingLists\Pages;

use App\Filament\SuperAdmin\Resources\MailingLists\MailingListResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMailingList extends EditRecord
{
    protected static string $resource = MailingListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
