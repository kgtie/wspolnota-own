<?php

namespace App\Filament\SuperAdmin\Resources\MailingMails\Pages;

use App\Filament\SuperAdmin\Resources\MailingMails\MailingMailResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMailingMail extends ViewRecord
{
    protected static string $resource = MailingMailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
