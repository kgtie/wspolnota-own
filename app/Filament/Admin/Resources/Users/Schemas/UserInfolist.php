<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dane parafianina')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('avatar_url')
                            ->label('Avatar')
                            ->circular()
                            ->imageSize(88)
                            ->columnSpanFull(),

                        TextEntry::make('full_name')
                            ->label('Imię i nazwisko')
                            ->placeholder('Brak'),

                        TextEntry::make('name')
                            ->label('Nazwa użytkownika'),

                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable()
                            ->copyMessage('Skopiowano email'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'Aktywne',
                                'inactive' => 'Nieaktywne',
                                'banned' => 'Zablokowane',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'banned' => 'danger',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Weryfikacja')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('email_verified_at')
                            ->label('Email zweryfikowany')
                            ->state(fn (User $record): string => $record->email_verified_at ? 'Tak' : 'Nie')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Tak' ? 'success' : 'warning'),

                        TextEntry::make('is_user_verified')
                            ->label('Zatwierdzenie proboszcza')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Zatwierdzony' : 'Oczekuje')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),

                        TextEntry::make('verifiedBy.full_name')
                            ->label('Zatwierdził')
                            ->placeholder('Brak'),

                        TextEntry::make('user_verified_at')
                            ->label('Data zatwierdzenia')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Brak'),
                    ]),

                Section::make('Historia')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Utworzono')
                            ->dateTime('d.m.Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Ostatnia zmiana')
                            ->since(),

                        TextEntry::make('last_login_at')
                            ->label('Ostatnie logowanie')
                            ->since()
                            ->placeholder('Brak'),

                        TextEntry::make('deleted_at')
                            ->label('Usunięty')
                            ->since()
                            ->placeholder('Nie'),
                    ]),
            ]);
    }
}
