<?php

namespace App\Filament\SuperAdmin\Resources\OfficeConversations\Schemas;

use App\Models\OfficeConversation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OfficeConversationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konwersacja')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')->label('ID')->badge(),
                        TextEntry::make('uuid')->label('UUID')->copyable(),

                        TextEntry::make('parish.name')
                            ->label('Parafia')
                            ->placeholder('Brak'),

                        TextEntry::make('parishioner.full_name')
                            ->label('Parafianin')
                            ->placeholder('Brak')
                            ->state(fn (OfficeConversation $record): string => $record->parishioner?->full_name
                                ?: $record->parishioner?->name
                                ?: $record->parishioner?->email
                                ?: 'Użytkownik usunięty'),

                        TextEntry::make('priest.full_name')
                            ->label('Administrator')
                            ->placeholder('Brak')
                            ->state(fn (OfficeConversation $record): string => $record->priest?->full_name
                                ?: $record->priest?->name
                                ?: $record->priest?->email
                                ?: 'Użytkownik usunięty'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => OfficeConversation::getStatusOptions()[$state] ?? $state)
                            ->color(fn (string $state): string => $state === OfficeConversation::STATUS_OPEN ? 'success' : 'gray'),

                        TextEntry::make('messages_count')
                            ->label('Wiadomości')
                            ->badge(),

                        TextEntry::make('last_message_at')
                            ->label('Ostatnia wiadomość')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Brak'),

                        TextEntry::make('closed_at')
                            ->label('Zamknięta')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie'),

                        TextEntry::make('created_at')
                            ->label('Utworzona')
                            ->dateTime('d.m.Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Aktualizacja')
                            ->since(),
                    ]),
            ]);
    }
}
