<?php

namespace App\Filament\SuperAdmin\Resources\MailingLists\Pages;

use App\Filament\SuperAdmin\Resources\MailingLists\MailingListResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMailingList extends CreateRecord
{
    protected static string $resource = MailingListResource::class;
}
