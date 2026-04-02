<?php

namespace App\Filament\SuperAdmin\Resources\AnnouncementSets\Schemas;

use App\Models\AnnouncementSet;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnouncementSetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Zestaw ogłoszeń')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')
                            ->label('Nazwa zestawu')
                            ->columnSpanFull(),

                        TextEntry::make('week_label')
                            ->label('Opis tygodnia')
                            ->placeholder('Brak'),

                        TextEntry::make('parish.name')
                            ->label('Parafia')
                            ->placeholder('Brak'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'published' => 'success',
                                'archived' => 'gray',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => AnnouncementSet::getStatusOptions()[$state] ?? $state),

                        TextEntry::make('published_at')
                            ->label('Data publikacji')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie opublikowano'),

                        TextEntry::make('effective_from')
                            ->label('Obowiązuje od')
                            ->date('d.m.Y'),

                        TextEntry::make('effective_to')
                            ->label('Obowiązuje do')
                            ->date('d.m.Y')
                            ->placeholder('Bez daty końcowej'),

                        TextEntry::make('items_count')
                            ->label('Liczba ogłoszeń')
                            ->state(fn (AnnouncementSet $record): string => (string) ($record->items_count ?? $record->items()->count()))
                            ->badge()
                            ->color('info'),

                        TextEntry::make('important_items_count')
                            ->label('Ogłoszenia ważne')
                            ->state(fn (AnnouncementSet $record): string => (string) ($record->important_items_count ?? $record->items()->where('is_important', true)->count()))
                            ->badge()
                            ->color('danger'),

                        TextEntry::make('lead')
                            ->label('Wstęp')
                            ->placeholder('Brak')
                            ->columnSpanFull(),

                        TextEntry::make('footer_notes')
                            ->label('Notatki końcowe')
                            ->placeholder('Brak')
                            ->columnSpanFull(),
                    ]),

                Section::make('AI i powiadomienia')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('summary_ai')
                            ->label('Streszczenie AI')
                            ->placeholder('Brak streszczenia')
                            ->columnSpanFull(),

                        TextEntry::make('summary_generated_at')
                            ->label('Data streszczenia')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie wygenerowano'),

                        TextEntry::make('summary_model')
                            ->label('Model AI')
                            ->placeholder('Brak'),

                        TextEntry::make('notifications_sent_at')
                            ->label('Wysyłka e-maili')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie wysłano'),

                        TextEntry::make('push_notification_sent_at')
                            ->label('Wysyłka push')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Oczekuje'),

                        TextEntry::make('email_notification_sent_at')
                            ->label('Wysyłka e-maili')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Oczekuje'),

                        TextEntry::make('notifications_recipients_count')
                            ->label('Liczba odbiorców')
                            ->placeholder('0'),
                    ]),

                Section::make('Historia wpisu')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('createdBy.full_name')
                            ->label('Utworzył')
                            ->placeholder('System'),

                        TextEntry::make('updatedBy.full_name')
                            ->label('Ostatnio edytował')
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
