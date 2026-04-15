<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs\Schemas;

use App\Filament\SuperAdmin\Resources\ActivityLogs\ActivityLogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Activitylog\Models\Activity;

/**
 * Szczegoly pojedynczego wpisu logu. Widok ma prowadzic operatora od
 * "co sie wydarzylo" do "na kim" i "z jakim kontekstem" bez zgadywania.
 */
class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ocena zdarzenia')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Czas')
                            ->dateTime('d.m.Y H:i:s'),

                        TextEntry::make('category')
                            ->label('Kategoria')
                            ->state(fn (Activity $record): string => ActivityLogResource::eventCategoryLabel($record))
                            ->badge()
                            ->color(fn (Activity $record): string => ActivityLogResource::eventCategoryColor($record)),

                        TextEntry::make('outcome')
                            ->label('Wynik')
                            ->state(fn (Activity $record): string => ActivityLogResource::outcomeLabel($record))
                            ->badge()
                            ->color(fn (Activity $record): string => ActivityLogResource::outcomeColor($record)),

                        TextEntry::make('id')
                            ->label('ID')
                            ->badge(),

                        TextEntry::make('log_name')
                            ->label('Strumien logow')
                            ->state(fn (Activity $record): string => ActivityLogResource::logNameLabel($record->log_name))
                            ->badge()
                            ->color(fn (Activity $record): string => ActivityLogResource::logNameColor($record->log_name)),

                        TextEntry::make('event_key')
                            ->label('Klucz eventu')
                            ->state(fn (Activity $record): string => $record->event ?: 'Brak')
                            ->copyable(),

                        TextEntry::make('event_label')
                            ->label('Event')
                            ->state(fn (Activity $record): string => ActivityLogResource::eventLabel($record->event))
                            ->badge()
                            ->color(fn (Activity $record): string => ActivityLogResource::eventColor($record)),

                        TextEntry::make('batch_uuid')
                            ->label('Batch UUID')
                            ->placeholder('Brak')
                            ->copyable(),

                        TextEntry::make('description')
                            ->label('Opis')
                            ->columnSpanFull(),
                    ]),

                Section::make('Powiazane encje')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('causer_ref')
                            ->label('Sprawca')
                            ->state(fn (Activity $record): string => ActivityLogResource::relationLabel(
                                $record->causer,
                                $record->causer_type,
                                $record->causer_id,
                            ))
                            ->url(fn (Activity $record): ?string => ActivityLogResource::relationUrl(
                                $record->causer,
                                $record->causer_type,
                                $record->causer_id,
                            ))
                            ->columnSpanFull(),

                        TextEntry::make('subject_ref')
                            ->label('Obiekt')
                            ->state(fn (Activity $record): string => ActivityLogResource::relationLabel(
                                $record->subject,
                                $record->subject_type,
                                $record->subject_id,
                            ))
                            ->url(fn (Activity $record): ?string => ActivityLogResource::relationUrl(
                                $record->subject,
                                $record->subject_type,
                                $record->subject_id,
                            ))
                            ->columnSpanFull(),

                        TextEntry::make('causer_type')
                            ->label('causer_type')
                            ->placeholder('Brak'),

                        TextEntry::make('causer_id')
                            ->label('causer_id')
                            ->placeholder('Brak'),

                        TextEntry::make('subject_type')
                            ->label('subject_type')
                            ->placeholder('Brak'),

                        TextEntry::make('subject_id')
                            ->label('subject_id')
                            ->placeholder('Brak'),
                    ]),

                Section::make('Kontekst audytu')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('context_summary')
                            ->label('Skrot kontekstu')
                            ->state(fn (Activity $record): string => ActivityLogResource::contextSummary($record))
                            ->columnSpanFull(),

                        TextEntry::make('context_json')
                            ->label('Wyciag kontekstu')
                            ->state(fn (Activity $record): string => ActivityLogResource::formattedJsonBlock(
                                ActivityLogResource::contextPretty($record),
                            ))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('Payload i zmiany')
                    ->schema([
                        TextEntry::make('properties_pretty')
                            ->label('properties (JSON)')
                            ->state(fn (Activity $record): string => ActivityLogResource::formattedJsonBlock(
                                ActivityLogResource::propertiesPretty($record),
                            ))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('changes_pretty')
                            ->label('changes (JSON)')
                            ->state(fn (Activity $record): string => ActivityLogResource::formattedJsonBlock(
                                ActivityLogResource::changesPretty($record),
                            ))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
