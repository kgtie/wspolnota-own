<?php

namespace App\Filament\SuperAdmin\Resources\OfficeConversations\Pages;

use App\Filament\SuperAdmin\Resources\OfficeConversations\OfficeConversationResource;
use App\Models\OfficeConversation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOfficeConversation extends ViewRecord
{
    protected static string $resource = OfficeConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('open')
                ->label('Otworz')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->status !== OfficeConversation::STATUS_OPEN)
                ->action(function (): void {
                    $record = $this->getRecord();

                    $record->update([
                        'status' => OfficeConversation::STATUS_OPEN,
                        'closed_at' => null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Konwersacja zostala otwarta.')
                        ->send();
                }),
            Action::make('close')
                ->label('Zamknij')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->visible(fn (): bool => $this->getRecord()->status !== OfficeConversation::STATUS_CLOSED)
                ->action(function (): void {
                    $record = $this->getRecord();

                    $record->update([
                        'status' => OfficeConversation::STATUS_CLOSED,
                        'closed_at' => now(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Konwersacja zostala zamknieta.')
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
