<?php

namespace App\Filament\SuperAdmin\Resources\Parishes\Schemas;

use App\Models\Parish;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ParishForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dane podstawowe')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Pelna nazwa')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('short_name')
                            ->label('Skrot')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->alphaDash()
                            ->unique(Parish::class, 'slug', ignoreRecord: true)
                            ->maxLength(100),

                        Toggle::make('is_active')
                            ->label('Aktywna')
                            ->default(true)
                            ->inline(false),

                        DatePicker::make('activated_at')
                            ->label('Data aktywacji')
                            ->native(false),

                        DatePicker::make('expiration_date')
                            ->label('Data wygasniecia')
                            ->native(false),

                        TextInput::make('subscription_fee')
                            ->label('Abonament')
                            ->numeric()
                            ->prefix('PLN')
                            ->step('0.01')
                            ->minValue(0),
                    ]),

                Section::make('Kontakt i adres')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('website')
                            ->label('WWW')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('city')
                            ->label('Miasto')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('street')
                            ->label('Ulica')
                            ->maxLength(255),

                        TextInput::make('postal_code')
                            ->label('Kod pocztowy')
                            ->maxLength(10),

                        TextInput::make('diocese')
                            ->label('Diecezja')
                            ->maxLength(255),

                        TextInput::make('decanate')
                            ->label('Dekanat')
                            ->maxLength(255),
                    ]),

                Section::make('Media parafii')
                    ->columns(2)
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('avatar_media')
                            ->label('Avatar')
                            ->collection('avatar')
                            ->image()
                            ->imageEditor()
                            ->maxSize(4096),

                        SpatieMediaLibraryFileUpload::make('cover_media')
                            ->label('Cover')
                            ->collection('cover')
                            ->image()
                            ->imageEditor()
                            ->maxSize(8192),
                    ]),

                Section::make('Ustawienia JSON')
                    ->description('Zaawansowane ustawienia per parafia (kolumna settings).')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('Settings')
                            ->keyLabel('Klucz')
                            ->valueLabel('Wartosc')
                            ->addActionLabel('Dodaj wpis')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
