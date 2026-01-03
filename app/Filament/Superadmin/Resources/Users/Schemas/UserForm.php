<?php

namespace App\Filament\Superadmin\Resources\Users\Schemas;

use App\Models\Parish;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dane konta')->schema([
                TextInput::make('name')->label('Login')->required()->maxLength(255),
                TextInput::make('full_name')->label('Imię i nazwisko')->required()->maxLength(255),
                TextInput::make('email')->label('E-mail')->email()->required()->maxLength(255),

                FileUpload::make('avatar')
                    ->label('Avatar')
                    ->disk('profiles')
                    ->directory('users/avatars')
                    ->image()
                    ->imageEditor(),
            ])->columns(2),

            Section::make('Uprawnienia i parafia')->schema([
                Select::make('role')
                    ->label('Rola')
                    ->options(User::roleOptions())
                    ->required(),

                Select::make('home_parish_id')
                    ->label('Parafia domowa')
                    ->options(fn () => Parish::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),

                Select::make('current_parish_id')
                    ->label('Ostatnio wybrana parafia')
                    ->options(fn () => Parish::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
            ])->columns(3),

            Section::make('Weryfikacja')->schema([
                TextInput::make('verification_code')
                    ->label('Kod weryfikacyjny (9 cyfr)')
                    ->disabled()
                    ->dehydrated(false),

                Toggle::make('is_user_verified')->label('Zweryfikowany (proboszcz)'),

                TextInput::make('password')
                    ->label('Hasło')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText('Zostaw puste, aby nie zmieniać hasła.'),
            ])->columns(3),
        ]);
    }
}
