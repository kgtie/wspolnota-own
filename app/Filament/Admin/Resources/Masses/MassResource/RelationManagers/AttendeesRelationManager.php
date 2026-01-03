<?php

namespace App\Filament\Admin\Resources\Masses\MassResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use App\Jobs\SendMassMessageJob;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class AttendeesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendees';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return 'Zapisani uczestnicy';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->label('Imię i nazwisko')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
            ])
            ->headerActions([
                Action::make('emailAll')
                    ->label('Wyślij e-mail')
                    ->form([
                        TextInput::make('subject')->label('Temat')->required()->maxLength(150),
                        Textarea::make('body')->label('Treść')->required()->rows(6),
                    ])
                    ->action(function (array $data) {
                        $mass = $this->getOwnerRecord();

                        SendMassMessageJob::dispatch(
                            $mass->id,
                            $data['subject'],
                            $data['body'],
                        );

                        Notification::make()
                            ->title('Zlecono wysyłkę e-maili')
                            ->success()
                            ->send();
                    }),

                Action::make('pushAll')
                    ->label('Wyślij push do wszystkich')
                    ->action(function () {
                        // TODO: wymaga tokenów FCM (kolumna lub tabela device_tokens)
                        Notification::make()
                            ->title('Push: potrzebujemy tokenów FCM')
                            ->body('Dodamy fcm_token / tabelę urządzeń i podepniemy Firebase.')
                            ->warning()
                            ->send();
                    }),
            ]);
    }
}
