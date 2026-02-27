<?php

namespace App\Filament\SuperAdmin\Resources\Users\Schemas;

use App\Models\Parish;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Imie i nazwisko')
                            ->maxLength(255),

                        TextInput::make('name')
                            ->label('Nazwa uzytkownika')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Haslo')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->maxLength(255),

                        Select::make('role')
                            ->label('Rola')
                            ->required()
                            ->options([
                                0 => 'Uzytkownik',
                                1 => 'Administrator',
                                2 => 'Superadministrator',
                            ])
                            ->native(false)
                            ->default(0),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'active' => 'Aktywne',
                                'inactive' => 'Nieaktywne',
                                'banned' => 'Zablokowane',
                            ])
                            ->native(false)
                            ->default('active'),
                    ]),

                Section::make('Powiazania parafialne')
                    ->columns(2)
                    ->schema([
                        Select::make('home_parish_id')
                            ->label('Parafia domowa')
                            ->options(fn (): array => Parish::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('current_parish_id')
                            ->label('Aktualna parafia')
                            ->options(fn (): array => Parish::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('last_managed_parish_id')
                            ->label('Ostatnio zarzadzana parafia')
                            ->options(fn (): array => Parish::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('managedParishes')
                            ->label('Parafie administrowane')
                            ->relationship('managedParishes', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Weryfikacja i sesja')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_user_verified')
                            ->label('Zatwierdzony przez proboszcza')
                            ->inline(false),

                        TextInput::make('verification_code')
                            ->label('Kod weryfikacyjny')
                            ->maxLength(9),

                        DateTimePicker::make('email_verified_at')
                            ->label('Email zweryfikowany')
                            ->seconds(false)
                            ->native(false),

                        DateTimePicker::make('user_verified_at')
                            ->label('Zatwierdzony dnia')
                            ->seconds(false)
                            ->native(false),

                        DateTimePicker::make('last_login_at')
                            ->label('Ostatnie logowanie')
                            ->seconds(false)
                            ->native(false),
                    ]),
            ]);
    }
}
