<?php

namespace App\Filament\Admin\Resources\Masses\Pages;

use App\Filament\Admin\Resources\Masses\MassResource;
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
                        Notification::make()
                            ->warning()
                            ->title('Brak uczestnikow do powiadomienia.')
                            ->body('Na te msze nie zapisaly sie jeszcze osoby z adresem email.')
                            ->send();

                        return;
                    }

                    $sent = 0;
                    $failed = 0;

                    foreach ($participants as $participant) {
                        try {
                            Mail::to($participant->email)->send(
                                new MassParticipantsMessage(
                                    mass: $record,
                                    sender: $admin,
                                    subjectLine: (string) $data['subject'],
                                    messageBody: (string) $data['message'],
                                    parishName: Filament::getTenant()?->name,
                                ),
                            );
                            $sent++;
                        } catch (Throwable) {
                            $failed++;
                        }
                    }

                    $notification = Notification::make()
                        ->title('Wysylka wiadomosci zakonczona.')
                        ->body("Wyslano: {$sent}, bledy: {$failed}");

                    if ($failed > 0) {
                        $notification->warning();
                    } else {
                        $notification->success();
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
