<?php

namespace App\Filament\Superadmin\Resources\UserResource\Pages;

use App\Filament\Superadmin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Podgląd'),

            Actions\Action::make('regenerateCode')
                ->label('Nowy kod')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Wygenerować nowy kod?')
                ->modalDescription('Stary kod weryfikacyjny przestanie działać.')
                ->action(function () {
                    $this->record->update([
                        'verification_code' => $this->generateVerificationCode(),
                    ]);
                    $this->refreshFormData(['verification_code']);

                    Notification::make()
                        ->title('Wygenerowano nowy kod weryfikacyjny')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->label('Usuń'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function generateVerificationCode(): string
    {
        do {
            $code = str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (\App\Models\User::where('verification_code', $code)->exists());

        return $code;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Użytkownik został zaktualizowany';
    }
}
