<?php

namespace App\Filament\Superadmin\Resources\Users\UserResource\Pages;

use App\Filament\Superadmin\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn () => auth()->id() === $this->record->id),

            RestoreAction::make(),

            ForceDeleteAction::make()
                ->disabled(fn () => auth()->id() === $this->record->id),
        ];
    }
}
