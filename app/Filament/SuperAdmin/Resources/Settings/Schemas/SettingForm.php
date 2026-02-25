<?php

namespace App\Filament\SuperAdmin\Resources\Settings\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Klucz ustawienia')
                    ->columns(2)
                    ->schema([
                        TextInput::make('group')
                            ->label('Grupa')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('general'),

                        TextInput::make('name')
                            ->label('Nazwa')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('site_name'),

                        Toggle::make('locked')
                            ->label('Zablokowane (tylko odczyt)')
                            ->inline(false)
                            ->helperText('Po wlaczeniu wartosc jest chroniona podczas migracji ustawien.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Wartosc JSON')
                    ->description('Pole payload jest przechowywane bezposrednio w tabeli settings.')
                    ->schema([
                        Textarea::make('payload')
                            ->label('Payload')
                            ->required()
                            ->rows(16)
                            ->json()
                            ->helperText('Wpisz poprawny JSON, np. "Wspolnota", true, 10, {"key":"value"}.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
