<?php

namespace App\Filament\SuperAdmin\Resources\Masses\Pages;

use App\Filament\SuperAdmin\Resources\Masses\MassResource;
use App\Models\Mass;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditMass extends EditRecord
{
    protected static string $resource = MassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            $this->statusAction('completed', 'Oznacz jako odprawiona', 'heroicon-o-check-circle', 'success'),
            $this->statusAction('scheduled', 'Oznacz jako zaplanowana', 'heroicon-o-clock', 'warning'),
            $this->statusAction('cancelled', 'Oznacz jako odwolana', 'heroicon-o-x-circle', 'danger'),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $admin = Filament::auth()->user();

        $data['updated_by_user_id'] = $admin instanceof User ? $admin->id : $data['updated_by_user_id'] ?? null;

        return $data;
    }

    protected function statusAction(string $status, string $label, string $icon, string $color): Action
    {
        return Action::make("set_status_{$status}")
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (): bool => $this->getCurrentStatus() !== $status)
            ->requiresConfirmation()
            ->action(function () use ($status): void {
                $record = $this->getRecord();
                $admin = Filament::auth()->user();

                if (! $record instanceof Mass) {
                    return;
                }

                $record->update([
                    'status' => $status,
                    'updated_by_user_id' => $admin instanceof User ? $admin->id : $record->updated_by_user_id,
                ]);

                $record->refresh();
            })
            ->successNotificationTitle('Status mszy zostal zaktualizowany.');
    }

    protected function getCurrentStatus(): ?string
    {
        $record = $this->getRecord();

        return $record instanceof Mass ? $record->status : null;
    }
}
