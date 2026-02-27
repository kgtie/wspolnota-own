<?php

namespace App\Filament\SuperAdmin\Resources\AnnouncementSets\Schemas;

use App\Models\AnnouncementSet;
use App\Models\Parish;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AnnouncementSetForm
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

                Section::make('Informacje glowne')
                    ->description('Definicja zestawu ogloszen parafialnych na wybrany okres.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Nazwa zestawu')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('week_label')
                            ->label('Opis tygodnia')
                            ->placeholder('np. XII tydzien zwykly')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        DatePicker::make('effective_from')
                            ->label('Obowiazuje od')
                            ->required()
                            ->native(false),

                        DatePicker::make('effective_to')
                            ->label('Obowiazuje do')
                            ->native(false)
                            ->rule('after_or_equal:effective_from'),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options(AnnouncementSet::getStatusOptions())
                            ->default('draft')
                            ->native(false)
                            ->live(),

                        DateTimePicker::make('published_at')
                            ->label('Data publikacji')
                            ->seconds(false)
                            ->native(false)
                            ->visible(fn (Get $get): bool => in_array($get('status'), ['published', 'archived'], true)),

                        Textarea::make('lead')
                            ->label('Wstep')
                            ->rows(3)
                            ->maxLength(5000)
                            ->columnSpanFull(),

                        Textarea::make('footer_notes')
                            ->label('Notatki koncowe')
                            ->rows(3)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),

                Section::make('AI i komunikacja')
                    ->description('Podglad automatycznego streszczenia i statusu wysylki emaili do parafian.')
                    ->columns(2)
                    ->schema([
                        Textarea::make('summary_ai')
                            ->label('Streszczenie AI')
                            ->rows(4)
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Streszczenie zostanie wygenerowane automatycznie po publikacji. ')
                            ->columnSpanFull(),

                        Placeholder::make('summary_generated_at_info')
                            ->label('Data streszczenia')
                            ->content(fn ($record): string => $record?->summary_generated_at?->format('d.m.Y H:i') ?? 'Brak'),

                        Placeholder::make('notifications_sent_at_info')
                            ->label('Wysylka email')
                            ->content(fn ($record): string => $record?->notifications_sent_at?->format('d.m.Y H:i') ?? 'Nie wyslano'),

                        Placeholder::make('notifications_recipients_info')
                            ->label('Liczba odbiorcow')
                            ->content(fn ($record): string => (string) ($record?->notifications_recipients_count ?? 0)),
                    ]),
            ]);
    }
}
