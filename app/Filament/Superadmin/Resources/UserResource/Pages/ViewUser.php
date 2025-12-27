<?php

namespace App\Filament\Superadmin\Resources\UserResource\Pages;

use App\Filament\Superadmin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Text;
use Filament\Forms;
use Filament\Notifications\Notification;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edytuj'),

            Actions\Action::make('verify')
                ->label('Zweryfikuj')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => !$this->record->is_user_verified && $this->record->home_parish_id !== null)
                ->form([
                    Text::make('Poproś użytkownika o okazanie 9-cyfrowego kodu weryfikacyjnego.')
                        ->color('neutral'),
                    Section::make()
                        ->schema([
                            Text::make('Oczekiwany kod:')
                                ->color('neutral'),
                            Text::make($this->record->verification_code ?? 'Brak kodu')
                                ->size('lg')
                                ->weight('bold')
                                ->fontFamily('mono')
                                ->copyable(),
                        ]),
                    Forms\Components\TextInput::make('entered_code')
                        ->label('Wprowadź kod od użytkownika')
                        ->placeholder('000000000')
                        ->mask('999999999')
                        ->required()
                        ->length(9)
                        ->helperText('Wpisz dokładnie 9 cyfr'),
                ])
                ->modalHeading('Weryfikacja użytkownika')
                ->modalDescription('Sprawdź kod weryfikacyjny okazany przez użytkownika.')
                ->modalSubmitActionLabel('Zweryfikuj')
                ->action(function (array $data): void {
                    if ($data['entered_code'] !== $this->record->verification_code) {
                        Notification::make()
                            ->title('Nieprawidłowy kod!')
                            ->body('Wprowadzony kod nie zgadza się z kodem użytkownika.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $this->record->update([
                        'is_user_verified' => true,
                        'user_verified_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Użytkownik zweryfikowany!')
                        ->body('Użytkownik ' . $this->record->name . ' został pomyślnie zweryfikowany.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('regenerate_code')
                ->label('Nowy kod')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => !$this->record->is_user_verified && $this->record->home_parish_id !== null)
                ->requiresConfirmation()
                ->modalHeading('Wygenerować nowy kod?')
                ->modalDescription('Poprzedni kod weryfikacyjny przestanie działać. Użytkownik będzie musiał okazać nowy kod.')
                ->modalSubmitActionLabel('Generuj nowy kod')
                ->action(function (): void {
                    $newCode = UserResource::generateVerificationCode();
                    $this->record->update(['verification_code' => $newCode]);

                    Notification::make()
                        ->title('Nowy kod wygenerowany!')
                        ->body('Nowy kod: ' . $newCode)
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->label('Usuń'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Flex::make([
                            ImageEntry::make('avatar')
                                ->label('')
                                ->circular()
                                ->size(100)
                                ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=6366f1&color=fff&size=200')
                                ->grow(false),
                            Section::make()
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('')
                                        ->size('lg')
                                        ->weight('bold'),
                                    TextEntry::make('email')
                                        ->label('')
                                        ->icon('heroicon-o-envelope')
                                        ->copyable(),
                                    TextEntry::make('role')
                                        ->label('')
                                        ->badge()
                                        ->formatStateUsing(fn (int $state): string => UserResource::getRoleOptions()[$state] ?? 'Nieznana')
                                        ->color(fn (int $state): string => UserResource::getRoleColor($state)),
                                ])
                                ->extraAttributes(['class' => 'border-0 shadow-none p-0']),
                        ])->from('md'),
                    ]),

                Section::make('Dane osobowe')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Imię i nazwisko')
                            ->placeholder('Nie podano'),
                        TextEntry::make('homeParish.short_name')
                            ->label('Parafia domowa')
                            ->placeholder('Brak')
                            ->icon('heroicon-o-building-library'),
                        IconEntry::make('email_verified_at')
                            ->label('Email zweryfikowany')
                            ->boolean()
                            ->getStateUsing(fn ($record): bool => (bool) $record->email_verified_at)
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        TextEntry::make('currentParish.short_name')
                            ->label('Aktualny kontekst')
                            ->placeholder('Brak'),
                    ]),

                Section::make('Weryfikacja parafianina')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('verification_code')
                            ->label('Kod weryfikacyjny (9 cyfr)')
                            ->fontFamily('mono')
                            ->size('lg')
                            ->weight('bold')
                            ->copyable()
                            ->placeholder('Brak'),
                        IconEntry::make('is_user_verified')
                            ->label('Status weryfikacji')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-clock')
                            ->trueColor('success')
                            ->falseColor('warning'),
                        TextEntry::make('user_verified_at')
                            ->label('Data weryfikacji')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie zweryfikowano'),
                    ]),

                Section::make('Zarządzane parafie')
                    ->visible(fn ($record): bool => $record->role >= 1)
                    ->schema([
                        RepeatableEntry::make('parishes')
                            ->label('')
                            ->schema([
                                TextEntry::make('short_name')
                                    ->label('')
                                    ->badge()
                                    ->color('info'),
                            ])
                            ->columns(4)
                            ->placeholder('Brak przypisanych parafii'),
                    ]),

                Section::make('Metadane')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('created_at')
                            ->label('Utworzono')
                            ->dateTime('d.m.Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('Ostatnia aktualizacja')
                            ->dateTime('d.m.Y H:i'),
                        TextEntry::make('email_verified_at')
                            ->label('Email potwierdzony')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie potwierdzono'),
                    ]),
            ]);
    }
}
