<?php

namespace App\Filament\Superadmin\Resources\Masses\Schemas;

use App\Models\Mass;
use App\Models\Parish;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("Parafia")->schema([
                Select::make('parish_id')
                    ->label('Parafia')
                    ->options(Parish::all()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
            ]),

            Section::make('Termin i miejsce')->schema([
                DateTimePicker::make('start_time')
                    ->label('Data i godzina')
                    ->seconds(false)
                    ->required(),

                Select::make('location')
                    ->label('Miejsce')
                    ->options(array_combine(Mass::getLocationOptions(), Mass::getLocationOptions()))
                    ->searchable()
                    ->required(),

                TextInput::make('celebrant')
                    ->label('Celebrans (opcjonalnie)')
                    ->maxLength(255),
            ])->columns(3),

            Section::make('Intencja i liturgia')->schema([
                Textarea::make('intention')
                    ->label('Intencja mszalna')
                    ->rows(4)
                    ->required(),

                Select::make('type')
                    ->label('Rodzaj')
                    ->options(Mass::getTypeOptions())
                    ->required(),

                Select::make('rite')
                    ->label('Ryt')
                    ->options(Mass::getRiteOptions())
                    ->required(),
            ])->columns(3),

            Section::make('Finanse')->schema([
                TextInput::make('stipend')
                    ->label('Stypendium (opcjonalnie)')
                    ->numeric()
                    ->prefix('zł')
                    ->helperText('Ta informacja nie jest publikowana.'),
            ]),
        ]);
    }
}
