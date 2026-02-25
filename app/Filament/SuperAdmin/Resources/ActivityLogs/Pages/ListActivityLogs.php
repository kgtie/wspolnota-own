<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs\Pages;

use App\Filament\SuperAdmin\Resources\ActivityLogs\ActivityLogResource;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Spatie\Activitylog\Models\Activity;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete_old_logs')
                ->label('Usun stare logi')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->schema([
                    TextInput::make('days')
                        ->label('Starsze niz (dni)')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(3650)
                        ->default(90),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $days = (int) ($data['days'] ?? 90);
                    $deletedCount = Activity::query()
                        ->where('created_at', '<', now()->subDays($days))
                        ->delete();

                    Notification::make()
                        ->success()
                        ->title('Usunieto stare logi.')
                        ->body("Usunieto {$deletedCount} rekordow starszych niz {$days} dni.")
                        ->send();
                }),

            Action::make('delete_all_logs')
                ->label('Wyczysc wszystkie logi')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $deletedCount = Activity::query()->delete();

                    Notification::make()
                        ->success()
                        ->title('Wyczyszczono activity_log.')
                        ->body("Usunieto lacznie {$deletedCount} rekordow.")
                        ->send();
                }),
        ];
    }
}
