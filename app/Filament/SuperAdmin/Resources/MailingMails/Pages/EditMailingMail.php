<?php

namespace App\Filament\SuperAdmin\Resources\MailingMails\Pages;

use App\Filament\SuperAdmin\Resources\MailingMails\MailingMailResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMailingMail extends EditRecord
{
    protected static string $resource = MailingMailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
