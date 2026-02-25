<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs\Schemas;

use App\Filament\SuperAdmin\Resources\ActivityLogs\ActivityLogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Activitylog\Models\Activity;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Podstawowe dane')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),

                        TextEntry::make('log_name')
                            ->label('Log')
                            ->placeholder('default')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('event')
                            ->label('Event')
                            ->placeholder('Brak')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('batch_uuid')
                            ->label('Batch UUID')
                            ->copyable()
                            ->copyMessage('Skopiowano UUID')
                            ->placeholder('Brak'),

                        TextEntry::make('description')
                            ->label('Opis')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Utworzono')
                            ->dateTime('d.m.Y H:i:s'),

                        TextEntry::make('updated_at')
                            ->label('Aktualizacja')
                            ->dateTime('d.m.Y H:i:s'),
                    ]),

                Section::make('Powiazania')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('causer_ref')
                            ->label('Sprawca')
                            ->state(fn (Activity $record): string => ActivityLogResource::relationLabel(
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

                Section::make('Properties i zmiany')
                    ->schema([
                        TextEntry::make('properties_pretty')
                            ->label('properties (JSON)')
                            ->state(fn (Activity $record): string => ActivityLogResource::propertiesPretty($record))
                            ->copyable()
                            ->copyMessage('Skopiowano properties')
                            ->columnSpanFull(),

                        TextEntry::make('changes_pretty')
                            ->label('changes (JSON)')
                            ->state(fn (Activity $record): string => ActivityLogResource::changesPretty($record))
                            ->copyable()
                            ->copyMessage('Skopiowano changes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
