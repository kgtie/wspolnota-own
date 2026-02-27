<?php

namespace App\Filament\SuperAdmin\Resources\OfficeConversations\Pages;

use App\Filament\SuperAdmin\Resources\OfficeConversations\OfficeConversationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOfficeConversation extends EditRecord
{
    protected static string $resource = OfficeConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
