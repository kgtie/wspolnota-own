<?php

namespace App\Filament\Admin\Resources\Parishioners\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

class ParishionersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->label('Imię i nazwisko')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->sortable(),

                // Kod ma być widoczny adminowi:
                TextColumn::make('verification_code')->label('Kod (9 cyfr)')->copyable()->toggleable(),

                IconColumn::make('is_user_verified')->label('Zweryfikowany')->boolean()->sortable(),
                TextColumn::make('user_verified_at')->label('Zweryfikowano')->dateTime()->toggleable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
                        Section::make('Dane')->schema([
                            TextEntry::make('full_name')->label('Imię i nazwisko'),
                            TextEntry::make('email')->label('E-mail'),
                            TextEntry::make('name')->label('Login'),
                        ])->columns(2),

                        Section::make('Weryfikacja')->schema([
                            TextEntry::make('verification_code')->label('Kod (9 cyfr)'),
                            IconEntry::make('is_user_verified')->label('Zweryfikowany')->boolean(),
                            TextEntry::make('user_verified_at')->label('Zweryfikowano')->dateTime(),
                        ])->columns(3),
                    ]),
            Action::make('verifyByCode')
                ->label('Zweryfikuj (kod)')
                ->visible(fn (User $record) => ! $record->is_user_verified)
                ->schema([
                    TextInput::make('code')
                        ->label('Kod podany przez użytkownika')
                        ->required()
                        ->numeric()
                            ->minLength(9)
                            ->maxLength(9),
                    ])
                    ->action(function (User $record, array $data): void {
                        $provided = (string) $data['code'];
                        $expected = (string) $record->verification_code;

                        if ($provided !== $expected) {
                            Notification::make()
                                ->title('Błędny kod')
                                ->body('Kod podany przez użytkownika nie zgadza się z kodem w systemie.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->forceFill([
                            'is_user_verified' => true,
                            'user_verified_at' => now(),
                        ])->save();

                        Notification::make()
                            ->title('Użytkownik zweryfikowany')
                            ->success()
                            ->send();
                    }),

                Action::make('regenerateCode')
                    ->label('Wygeneruj nowy kod')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->regenerateVerificationCode();

                        Notification::make()
                            ->title('Wygenerowano nowy kod')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
