<?php

namespace App\Filament\SuperAdmin\Resources\Users\Schemas;

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
                Section::make('Uzytkownik')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('avatar_preview')
                            ->label('Avatar')
                            ->state(fn (User $record): string => $record->avatar_url)
                            ->circular()
                            ->imageSize(88)
                            ->columnSpanFull(),

                        TextEntry::make('full_name')->label('Imie i nazwisko')->placeholder('Brak'),
                        TextEntry::make('name')->label('Nazwa'),
                        TextEntry::make('email')->label('Email')->copyable(),

                        TextEntry::make('role')
                            ->label('Rola')
                            ->badge()
                            ->formatStateUsing(fn (int $state): string => match ($state) {
                                2 => 'Superadministrator',
                                1 => 'Administrator',
                                default => 'Uzytkownik',
                            })
                            ->color(fn (int $state): string => match ($state) {
                                2 => 'danger',
                                1 => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'banned' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('homeParish.name')->label('Parafia domowa')->placeholder('Brak'),
                        TextEntry::make('managed_parishes_count')->label('Parafie administrowane')->badge(),
                        TextEntry::make('registered_masses_count')->label('Zapisy na msze')->badge(),

                        TextEntry::make('email_verified_at')->label('Email verified')->dateTime('d.m.Y H:i')->placeholder('Nie'),
                        TextEntry::make('is_user_verified')
                            ->label('Zatwierdzenie')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie')
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),

                        TextEntry::make('last_login_at')->label('Ostatnie logowanie')->since()->placeholder('Brak'),
                        TextEntry::make('created_at')->label('Utworzono')->dateTime('d.m.Y H:i'),
                    ]),
            ]);
    }
}
