<?php

namespace App\Filament\SuperAdmin\Resources\Masses\Schemas;

use App\Models\Mass;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MassInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Harmonogram i intencja')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('celebration_at')
                            ->label('Data i godzina')
                            ->dateTime('d.m.Y H:i'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (string $state): string => Mass::getStatusOptions()[$state] ?? $state)
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'warning',
                            }),

                        TextEntry::make('mass_kind')
                            ->label('Rodzaj mszy')
                            ->formatStateUsing(fn (string $state): string => Mass::getMassKindOptions()[$state] ?? $state)
                            ->badge(),

                        TextEntry::make('mass_type')
                            ->label('Typ intencji')
                            ->formatStateUsing(fn (string $state): string => Mass::getMassTypeOptions()[$state] ?? $state)
                            ->badge(),

                        TextEntry::make('participants_count')
                            ->label('Liczba uczestnikow')
                            ->state(fn (Mass $record): string => (string) ($record->participants_count ?? $record->participants()->count()))
                            ->badge()
                            ->color(fn (Mass $record): string => (($record->participants_count ?? 0) > 0) ? 'success' : 'gray'),

                        TextEntry::make('reminder_push_24h_count')
                            ->label('Push 24h')
                            ->state(fn (Mass $record): string => (string) ($record->reminder_push_24h_count ?? 0))
                            ->badge()
                            ->color('info'),

                        TextEntry::make('reminder_push_8h_count')
                            ->label('Push 8h')
                            ->state(fn (Mass $record): string => (string) ($record->reminder_push_8h_count ?? 0))
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('reminder_push_1h_count')
                            ->label('Push 1h')
                            ->state(fn (Mass $record): string => (string) ($record->reminder_push_1h_count ?? 0))
                            ->badge()
                            ->color('danger'),

                        TextEntry::make('reminder_email_count')
                            ->label('Email 5:00')
                            ->state(fn (Mass $record): string => (string) ($record->reminder_email_count ?? 0))
                            ->badge()
                            ->color('success'),

                        TextEntry::make('parish.name')
                            ->label('Parafia')
                            ->placeholder('Brak'),

                        TextEntry::make('intention_title')
                            ->label('Intencja glowna')
                            ->columnSpanFull(),

                        TextEntry::make('intention_details')
                            ->label('Szczegoly intencji')
                            ->placeholder('Brak dodatkowych szczegolow')
                            ->columnSpanFull(),
                    ]),

                Section::make('Celebrans i finanse')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('celebrant_name')
                            ->label('Ksiadz celebrujacy')
                            ->placeholder('Nie przypisano'),

                        TextEntry::make('location')
                            ->label('Miejsce')
                            ->placeholder('Nie podano'),

                        TextEntry::make('stipendium_amount')
                            ->label('Stypendium')
                            ->state(fn (Mass $record): string => $record->stipendium_amount !== null
                                ? number_format((float) $record->stipendium_amount, 2, ',', ' ').' PLN'
                                : 'Brak'),

                        TextEntry::make('stipendium_paid_at')
                            ->label('Data przyjecia stypendium')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie oplacono'),

                        TextEntry::make('notes')
                            ->label('Notatki')
                            ->placeholder('Brak notatek')
                            ->columnSpanFull(),
                    ]),

                Section::make('Historia wpisu')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('createdBy.full_name')
                            ->label('Utworzyl')
                            ->placeholder('System'),

                        TextEntry::make('updatedBy.full_name')
                            ->label('Ostatnio edytowal')
                            ->placeholder('Brak danych'),

                        TextEntry::make('created_at')
                            ->label('Utworzono')
                            ->dateTime('d.m.Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Ostatnia zmiana')
                            ->since(),
                    ]),
            ]);
    }
}
