<?php

namespace App\Filament\SuperAdmin\Resources\Settings\Pages;

use App\Filament\SuperAdmin\Resources\Settings\SettingResource;
use App\Filament\SuperAdmin\Widgets\ApplicationHealthWidget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_settings_cache')
                ->label('Wyczysc cache ustawien')
                ->icon('heroicon-o-bolt')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    Artisan::call('settings:clear-cache');

                    Notification::make()
                        ->success()
                        ->title('Cache ustawien zostal wyczyszczony.')
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ApplicationHealthWidget::class,
        ];
    }
}
