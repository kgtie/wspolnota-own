<?php

namespace App\Filament\Admin\Resources\ParishionerResource\Pages;

use App\Filament\Admin\Resources\ParishionerResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class ViewParishioner extends ViewRecord
{
    protected static string $resource = ParishionerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edytuj'),

            Actions\Action::make('verify')
                ->label('Zweryfikuj')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => !$this->record->is_user_verified)
                ->form([
                    Text::make('Poproś parafianina o okazanie 9-cyfrowego kodu weryfikacyjnego.')
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
                        ->label('Wprowadź kod od parafianina')
                        ->placeholder('000000000')
                        ->mask('999999999')
                        ->required()
                        ->length(9),
                ])
                ->modalHeading('Weryfikacja parafianina')
                ->modalSubmitActionLabel('Zweryfikuj')
                ->action(function (array $data): void {
                    if ($data['entered_code'] !== $this->record->verification_code) {
                        Notification::make()
                            ->title('Nieprawidłowy kod!')
                            ->body('Wprowadzony kod nie zgadza się.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $this->record->update([
                        'is_user_verified' => true,
                        'user_verified_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Parafianin zweryfikowany!')
                        ->body($this->record->name . ' został pomyślnie zweryfikowany.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('regenerate_code')
                ->label('Nowy kod')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => !$this->record->is_user_verified)
                ->requiresConfirmation()
                ->modalHeading('Wygenerować nowy kod?')
                ->modalDescription('Poprzedni kod weryfikacyjny przestanie działać.')
                ->modalSubmitActionLabel('Generuj nowy kod')
                ->action(function (): void {
                    $newCode = ParishionerResource::generateVerificationCode();
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
                                ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=3b82f6&color=fff&size=200')
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
                        IconEntry::make('email_verified_at')
                            ->label('Email zweryfikowany')
                            ->boolean()
                            ->getStateUsing(fn ($record): bool => (bool) $record->email_verified_at)
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ]),

                Section::make('Weryfikacja')
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

                Section::make('Metadane')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('created_at')
                            ->label('Zarejestrowano')
                            ->dateTime('d.m.Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('Ostatnia aktualizacja')
                            ->dateTime('d.m.Y H:i'),
                    ]),
            ]);
    }
}
