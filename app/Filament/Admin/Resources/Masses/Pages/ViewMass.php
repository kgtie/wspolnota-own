<?php

namespace App\Filament\Admin\Resources\Masses\Pages;

use App\Filament\Admin\Resources\Masses\MassResource;
use App\Mail\MassParticipantsMessage;
use App\Models\Mass;
use App\Models\User;
use App\Support\Notifications\NotificationPreferenceResolver;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ViewMass extends ViewRecord
{
    protected static string $resource = MassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            $this->statusAction('completed', 'Oznacz jako odprawiona', 'heroicon-o-check-circle', 'success'),
            $this->statusAction('scheduled', 'Oznacz jako zaplanowana', 'heroicon-o-clock', 'warning'),
            $this->statusAction('cancelled', 'Oznacz jako odwołaną', 'heroicon-o-x-circle', 'danger'),
            Action::make('send_email_to_participants')
                ->label('Wyślij e-mail do uczestników')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->schema([
                    TextInput::make('subject')
                        ->label('Temat')
                        ->required()
                        ->maxLength(160)
                        ->default(function (): string {
                            $record = $this->getRecord();

                            if (! $record instanceof Mass) {
                                return 'Informacja dotycząca Mszy Świętej';
                            }

                            $date = $record->celebration_at?->format('d.m.Y H:i') ?? 'bez terminu';

                            return "Informacja dotycząca Mszy ({$date})";
                        }),
                    Textarea::make('message')
                        ->label('Treść wiadomości')
                        ->required()
                        ->rows(10)
                        ->maxLength(5000),
                ])
                ->action(function (array $data, NotificationPreferenceResolver $preferences): void {
                    $record = $this->getRecord();
                    $admin = Filament::auth()->user();

                    if (! $record instanceof Mass || ! $admin instanceof User) {
                        return;
                    }

                    $participants = $record->participants()
                        ->with('notificationPreference')
                        ->where('users.status', 'active')
                        ->whereNotNull('users.email')
                        ->get();

                    $participants = $participants
                        ->filter(fn (User $participant): bool => $preferences->wantsEmail($participant, 'manual_messages'))
                        ->values();

                    if ($participants->isEmpty()) {
                        if ($admin instanceof User) {
                            activity('admin-mass-management')
                                ->causedBy($admin)
                                ->performedOn($record)
                                ->event('mass_participants_email_skipped')
                                ->withProperties([
                                    'parish_id' => Filament::getTenant()?->getKey(),
                                    'reason' => 'no_recipients',
                                ])
                                ->log('Pominięto wysyłkę e-maila do uczestników Mszy: brak odbiorców.');
                        }

                        Notification::make()
                            ->warning()
                            ->title('Brak uczestników do powiadomienia.')
                            ->body('Na tę Mszę nie zapisały się jeszcze osoby z adresem e-mail.')
                            ->send();

                        return;
                    }

                    $queued = 0;
                    $failed = 0;

                    foreach ($participants as $participant) {
                        try {
                            Mail::to($participant->email)->queue(
                                new MassParticipantsMessage(
                                    mass: $record,
                                    sender: $admin,
                                    subjectLine: (string) $data['subject'],
                                    messageBody: (string) $data['message'],
                                    parishName: Filament::getTenant()?->name,
                                ),
                            );
                            $queued++;
                        } catch (Throwable) {
                            $failed++;
                        }
                    }

                    $notification = Notification::make()
                        ->title('Kolejkowanie wiadomości zakończone.')
                        ->body("Zakolejkowano: {$queued}, bledy: {$failed}");

                    if ($failed > 0) {
                        $notification->warning();
                    } else {
                        $notification->success();
                    }

                    if ($admin instanceof User) {
                        activity('admin-mass-management')
                            ->causedBy($admin)
                            ->performedOn($record)
                            ->event('mass_participants_email_sent')
                            ->withProperties([
                                'parish_id' => Filament::getTenant()?->getKey(),
                                'participants_count' => $participants->count(),
                                'queued_count' => $queued,
                                'failed_count' => $failed,
                                'subject' => (string) $data['subject'],
                                'message_length' => mb_strlen((string) $data['message']),
                            ])
                            ->log('Proboszcz zakolejkował wiadomość e-mail do uczestników Mszy.');
                    }

                    $notification->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
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
            ->successNotificationTitle('Status mszy został zaktualizowany.');
    }

    protected function getCurrentStatus(): ?string
    {
        $record = $this->getRecord();

        return $record instanceof Mass ? $record->status : null;
    }
}
