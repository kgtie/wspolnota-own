<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('verify_user_with_code')
                ->label('Zatwierdź kodem')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->visible(fn (): bool => ! $this->isRecordVerified())
                ->schema([
                    TextInput::make('provided_code')
                        ->label('Kod podany przez parafianina')
                        ->required()
                        ->minLength(9)
                        ->maxLength(9)
                        ->regex('/^\d{9}$/'),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();

                    if (! $record instanceof User) {
                        return;
                    }

                    $verifiedBy = Filament::auth()->user();
                    $providedCode = (string) ($data['provided_code'] ?? '');

                    $wasVerified = UserResource::verifyRecordWithCode(
                        $record,
                        $providedCode,
                        $verifiedBy instanceof User ? $verifiedBy : null,
                    );

                    if (! $wasVerified) {
                        throw ValidationException::withMessages([
                            'provided_code' => 'Podany kod jest nieprawidłowy. Wygeneruj nowy kod i spróbuj ponownie.',
                        ]);
                    }

                    $record->refresh();
                    $this->refreshFormData(['is_user_verified', 'user_verified_at', 'verified_by_user_id']);
                })
                ->successNotificationTitle('Parafianin został zatwierdzony.'),
            Action::make('unverify_user')
                ->label('Cofnij zatwierdzenie')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->visible(fn (): bool => $this->isRecordVerified())
                ->requiresConfirmation()
                ->action(function (): void {
                    $record = $this->getRecord();

                    if (! $record instanceof User) {
                        return;
                    }

                    UserResource::unverifyRecord($record);
                    $record->refresh();
                    $this->refreshFormData(['is_user_verified', 'user_verified_at', 'verified_by_user_id']);
                })
                ->successNotificationTitle('Zatwierdzenie zostało cofnięte.'),
            Action::make('regenerate_code')
                ->label('Nowy kod 9-cyfrowy')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function (): void {
                    $record = $this->getRecord();

                    if (! $record instanceof User) {
                        return;
                    }

                    UserResource::regenerateVerificationCode($record);
                })
                ->successNotificationTitle('Wygenerowano nowy kod dla parafianina.'),
            Action::make('send_password_reset_link')
                ->label('Wyślij link resetu hasła')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $record = $this->getRecord();

                    if (! $record instanceof User) {
                        return;
                    }

                    $status = Password::sendResetLink(['email' => $record->email]);

                    if ($status !== Password::RESET_LINK_SENT) {
                        Notification::make()
                            ->danger()
                            ->title('Nie udało się wysłać linku resetu hasła.')
                            ->body(__($status))
                            ->send();

                        return;
                    }

                    $admin = Filament::auth()->user();

                    if ($admin instanceof User) {
                        activity('admin-user-management')
                            ->causedBy($admin)
                            ->performedOn($record)
                            ->event('password_reset_link_sent')
                            ->withProperties([
                                'recipient_email' => $record->email,
                                'parish_id' => Filament::getTenant()?->getKey(),
                            ])
                            ->log('Proboszcz wysłał parafianinowi link resetu hasła.');
                    }

                    Notification::make()
                        ->success()
                        ->title('Wysłano link resetu hasła.')
                        ->body('Parafianin otrzyma wiadomość z instrukcją ustawienia nowego hasła.')
                        ->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        $data['role'] = 0;
        $data['verification_code'] = filled($data['verification_code'] ?? null)
            ? $data['verification_code']
            : (($record instanceof User && filled($record->verification_code))
                ? $record->verification_code
                : UserResource::generateUniqueVerificationCode());

        if ($record instanceof User) {
            // Tożsamość parafianina jest tylko do odczytu dla proboszcza.
            $data['full_name'] = $record->full_name;
            $data['name'] = $record->name;
            $data['email'] = $record->email;

            // Weryfikacja użytkownika jest możliwa wyłącznie przez akcję z podaniem kodu.
            $data['is_user_verified'] = (bool) $record->is_user_verified;
            $data['user_verified_at'] = $record->user_verified_at;
            $data['verified_by_user_id'] = $record->verified_by_user_id;
        }

        return $data;
    }

    protected function isRecordVerified(): bool
    {
        $record = $this->getRecord();

        return $record instanceof User && (bool) $record->is_user_verified;
    }
}
