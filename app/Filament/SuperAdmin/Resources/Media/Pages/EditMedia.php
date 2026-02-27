<?php

namespace App\Filament\SuperAdmin\Resources\Media\Pages;

use App\Filament\SuperAdmin\Resources\Media\MediaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['uploaded_file']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
