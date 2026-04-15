<?php

namespace App\Filament\SuperAdmin\Resources\Parishes\Schemas;

use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ParishInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Parafia')
                    ->columns(2)
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('avatar_media')
                            ->label('Avatar')
                            ->collection('avatar')
                            ->conversion('thumb'),

                        SpatieMediaLibraryImageEntry::make('cover_media')
                            ->label('Cover')
                            ->collection('cover')
                            ->conversion('preview'),

                        TextEntry::make('name')
                            ->label('Nazwa')
                            ->columnSpanFull(),

                        TextEntry::make('short_name')->label('Skrot'),
                        TextEntry::make('slug')->label('Slug'),

                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Aktywna' : 'Nieaktywna')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),

                        TextEntry::make('city')->label('Miasto'),
                        TextEntry::make('email')->label('Email')->placeholder('Brak'),
                        TextEntry::make('website')->label('WWW')->placeholder('Brak'),

                        TextEntry::make('parishioners_count')->label('Parafianie')->badge(),
                        TextEntry::make('admins_count')->label('Administratorzy')->badge(),
                        TextEntry::make('masses_count')->label('Msze')->badge(),
                        TextEntry::make('announcement_sets_count')->label('Zestawy ogłoszeń')->badge(),
                        TextEntry::make('news_posts_count')->label('Aktualnosci')->badge(),
                        TextEntry::make('office_conversations_count')->label('Konwersacje online')->badge(),

                        TextEntry::make('created_at')->label('Utworzono')->dateTime('d.m.Y H:i'),
                        TextEntry::make('updated_at')->label('Aktualizacja')->since(),
                    ]),
            ]);
    }
}
