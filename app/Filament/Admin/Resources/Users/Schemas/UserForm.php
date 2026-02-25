<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('role')
                    ->default(0),

                Section::make('Dane parafianina')
                    ->description('Podstawowe dane konta parafianina.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Imię i nazwisko')
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nazwa użytkownika')
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true),

                        Select::make('status')
                            ->label('Status konta')
                            ->options([
                                'active' => 'Aktywne',
                                'inactive' => 'Nieaktywne',
                                'banned' => 'Zablokowane',
                            ])
                            ->default('active')
                            ->required(),
                    ]),

                Section::make('Weryfikacja i zatwierdzenie')
                    ->description('Status weryfikacji email i zatwierdzenia przez proboszcza.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_user_verified')
                            ->label('Zatwierdzony przez proboszcza')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Zmiana zatwierdzenia jest możliwa tylko akcją "Zatwierdź kodem".'),

                        DateTimePicker::make('email_verified_at')
                            ->label('Email zweryfikowany dnia')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('user_verified_at')
                            ->label('Data zatwierdzenia')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(false),
                    ]),

            ]);
    }
}
