<?php

namespace App\Filament\SuperAdmin\Resources\Masses\Pages;

use App\Filament\SuperAdmin\Resources\Masses\MassResource;
use App\Mail\MassParticipantsMessage;
use App\Models\Mass;
use App\Models\User;
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
            $this->statusAction('cancelled', 'Oznacz jako odwolana', 'heroicon-o-x-circle', 'danger'),
            Action::make('send_email_to_participants')
                ->label('Wyslij email do uczestnikow')
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
                                return 'Informacja dotyczaca mszy swietej';
                            }

                            $date = $record->celebration_at?->format('d.m.Y H:i') ?? 'bez terminu';

                            return "Informacja dotyczaca mszy ({$date})";
                        }),
                    Textarea::make('message')
                        ->label('Tresc wiadomosci')
                        ->required()
                        ->rows(10)
                        ->maxLength(5000),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();
                    $admin = Filament::auth()->user();

                    if (! $record instanceof Mass || ! $admin instanceof User) {
                        return;
                    }

                    $participants = $record->participants()
                        ->where('users.status', 'active')
                        ->whereNotNull('users.email')
                        ->get();

                    if ($participants->isEmpty()) {
                        if ($admin instanceof User) {
                            activity('admin-mass-management')
                                ->causedBy($admin)
                                ->performedOn($record)
                                ->event('mass_participants_email_skipped')
                                ->withProperties([
                                    'parish_id' => $record->parish_id,
                                    'reason' => 'no_recipients',
                                ])
                                ->log('Pominieto wysylke email do uczestnikow mszy: brak odbiorcow.');
                        }

                        Notification::make()
                            ->warning()
                            ->title('Brak uczestnikow do powiadomienia.')
                            ->body('Na te msze nie zapisaly sie jeszcze osoby z adresem email.')
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
                                    parishName: $record->parish?->name,
                                ),
                            );
                            $queued++;
                        } catch (Throwable) {
                            $failed++;
                        }
                    }

                    $notification = Notification::make()
                        ->title('Kolejkowanie wiadomosci zakonczone.')
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
                                'parish_id' => $record->parish_id,
                                'participants_count' => $participants->count(),
                                'queued_count' => $queued,
                                'failed_count' => $failed,
                                'subject' => (string) $data['subject'],
                                'message_length' => mb_strlen((string) $data['message']),
                            ])
                            ->log('Proboszcz zakolejkowal wiadomosc email do uczestnikow mszy.');
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
