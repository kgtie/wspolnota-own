<?php

namespace App\Filament\Admin\Pages\Tenancy;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditParishProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Profil parafii';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-office-2';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dane parafii')
                ->schema([
                    TextInput::make('name')
                        ->label('Pełna nazwa')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('short_name')
                        ->label('Krótka nazwa')
                        ->required()
                        ->maxLength(255),

                    // Admin nie powinien zmieniać slug (bezpieczniej)
                    TextInput::make('slug')
                        ->label('Slug')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Section::make('Kontakt')
                ->schema([
                    TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label('Telefon')
                        ->maxLength(50),

                    TextInput::make('website')
                        ->label('WWW')
                        ->url()
                        ->maxLength(255),
                ]),

            Section::make('Adres')
                ->schema([
                    TextInput::make('street')
                        ->label('Ulica')
                        ->maxLength(255),

                    TextInput::make('postal_code')
                        ->label('Kod pocztowy')
                        ->maxLength(10),

                    TextInput::make('city')
                        ->label('Miasto')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('diocese')
                        ->label('Diecezja')
                        ->maxLength(255),

                    TextInput::make('decanate')
                        ->label('Dekanat')
                        ->maxLength(255),
                ]),

            Section::make('Grafiki')
                ->schema([
                    FileUpload::make('avatar')
                        ->label('Logo / avatar')
                        ->disk('profiles')
                        ->directory('parishes/avatars')
                        ->image()
                        ->imageEditor(),

                    FileUpload::make('cover_image')
                        ->label('Zdjęcie w tle')
                        ->disk('profiles')
                        ->directory('parishes/covers')
                        ->image()
                        ->imageEditor(),
                ]),
        ]);
    }
}
