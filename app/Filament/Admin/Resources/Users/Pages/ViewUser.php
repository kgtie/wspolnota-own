<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Mail\ParishPriestMessage;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
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

                    $admin = Filament::auth()->user();

                    UserResource::unverifyRecord(
                        $record,
                        $admin instanceof User ? $admin : null,
                    );
                    $record->refresh();
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

                    $admin = Filament::auth()->user();

                    UserResource::regenerateVerificationCode(
                        $record,
                        $admin instanceof User ? $admin : null,
                    );
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
                        $admin = Filament::auth()->user();

                        if ($admin instanceof User) {
                            activity('admin-user-management')
                                ->causedBy($admin)
                                ->performedOn($record)
                                ->event('password_reset_link_send_failed')
                                ->withProperties([
                                    'recipient_email' => $record->email,
                                    'parish_id' => Filament::getTenant()?->getKey(),
                                    'status' => $status,
                                ])
                                ->log('Próba wysłania linku resetu hasła zakończona niepowodzeniem.');
                        }

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
            Action::make('send_email_from_priest')
                ->label('Wyślij email do parafianina')
                ->icon('heroicon-o-envelope-open')
                ->color('info')
                ->schema([
                    TextInput::make('subject')
                        ->label('Temat')
                        ->required()
                        ->maxLength(160),
                    Textarea::make('message')
                        ->label('Treść wiadomości')
                        ->required()
                        ->rows(10)
                        ->maxLength(5000),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();
                    $admin = Filament::auth()->user();

                    if (! $record instanceof User || ! $admin instanceof User) {
                        return;
                    }

                    try {
                        Mail::to($record->email)->send(
                            new ParishPriestMessage(
                                recipient: $record,
                                sender: $admin,
                                subjectLine: (string) $data['subject'],
                                messageBody: (string) $data['message'],
                                parishName: Filament::getTenant()?->name,
                            ),
                        );
                    } catch (Throwable $exception) {
                        activity('admin-user-management')
                            ->causedBy($admin)
                            ->performedOn($record)
                            ->event('parish_priest_message_send_failed')
                            ->withProperties([
                                'recipient_email' => $record->email,
                                'subject' => (string) $data['subject'],
                                'message_length' => mb_strlen((string) $data['message']),
                                'parish_id' => Filament::getTenant()?->getKey(),
                                'exception' => $exception::class,
                            ])
                            ->log('Nie udało się wysłać wiadomości email do parafianina.');

                        Notification::make()
                            ->danger()
                            ->title('Nie udało się wysłać wiadomości.')
                            ->body($exception->getMessage())
                            ->send();

                        return;
                    }

                    activity('admin-user-management')
                        ->causedBy($admin)
                        ->performedOn($record)
                        ->event('parish_priest_message_sent')
                        ->withProperties([
                            'recipient_email' => $record->email,
                            'subject' => (string) $data['subject'],
                            'message_length' => mb_strlen((string) $data['message']),
                            'parish_id' => Filament::getTenant()?->getKey(),
                        ])
                        ->log('Proboszcz wysłał wiadomość email do parafianina.');

                    Notification::make()
                        ->success()
                        ->title('Wiadomość została wysłana.')
                        ->body('Parafianin otrzyma email z informacją, że wiadomość pochodzi od proboszcza.')
                        ->send();
                }),
        ];
    }

    protected function isRecordVerified(): bool
    {
        $record = $this->getRecord();

        return $record instanceof User && (bool) $record->is_user_verified;
    }
}
