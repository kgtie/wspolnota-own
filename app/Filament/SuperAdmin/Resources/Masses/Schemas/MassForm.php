<?php

namespace App\Filament\SuperAdmin\Resources\Masses\Schemas;

use App\Models\Mass;
use App\Models\Parish;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parish_id')
                    ->label('Parafia')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Parish::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                Hidden::make('created_by_user_id'),
                Hidden::make('updated_by_user_id'),

                Section::make('Harmonogram i intencja')
                    ->description('Podstawowe informacje o dacie, godzinie i tresci intencji.')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('celebration_at')
                            ->label('Data i godzina mszy')
                            ->required()
                            ->seconds(false)
                            ->native(false),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options(Mass::getStatusOptions())
                            ->default('scheduled')
                            ->native(false),

                        Select::make('mass_kind')
                            ->label('Rodzaj mszy')
                            ->required()
                            ->options(Mass::getMassKindOptions())
                            ->native(false),

                        Select::make('mass_type')
                            ->label('Typ intencji')
                            ->required()
                            ->options(Mass::getMassTypeOptions())
                            ->native(false),

                        TextInput::make('intention_title')
                            ->label('Intencja glowna')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('intention_details')
                            ->label('Szczegoly intencji')
                            ->rows(3)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Celebrans i miejsce')
                    ->description('Dane ksiedza oraz miejsca odprawienia liturgii.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('celebrant_name')
                            ->label('Ksiadz celebrujacy')
                            ->placeholder('np. ks. Jan Kowalski')
                            ->maxLength(255),

                        TextInput::make('location')
                            ->label('Miejsce')
                            ->placeholder('np. Kosciol parafialny')
                            ->maxLength(255),

                        Textarea::make('notes')
                            ->label('Notatki kancelaryjne')
                            ->rows(3)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Stypendium')
                    ->description('Stypendium za msze jest opcjonalne i moze byc uzupelnione pozniej.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('stipendium_amount')
                            ->label('Kwota stypendium')
                            ->numeric()
                            ->prefix('PLN')
                            ->inputMode('decimal')
                            ->minValue(0)
                            ->step(0.01)
                            ->placeholder('np. 120.00'),

                        DateTimePicker::make('stipendium_paid_at')
                            ->label('Data przyjecia stypendium')
                            ->seconds(false)
                            ->native(false),
                    ]),
            ]);
    }
}
