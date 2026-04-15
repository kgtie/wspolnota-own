<?php

namespace App\Filament\SuperAdmin\Resources\Media\Schemas;

use App\Filament\SuperAdmin\Resources\Media\MediaResource;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Podgląd')
                    ->schema([
                        ImageEntry::make('preview')
                            ->hiddenLabel()
                            ->state(fn (Media $record): ?string => str_starts_with((string) ($record->mime_type ?? ''), 'image/')
                                ? $record->getUrl()
                                : null)
                            ->height(240)
                            ->placeholder('Brak podgladu dla tego typu pliku'),
                    ]),

                Section::make('Szczegoly')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')->label('ID')->badge(),
                        TextEntry::make('uuid')->label('UUID')->placeholder('Brak')->copyable(),

                        TextEntry::make('model_type')
                            ->label('Model')
                            ->badge()
                            ->state(fn (Media $record): string => MediaResource::resolveModelTypeLabel($record->model_type)),

                        TextEntry::make('model_id')->label('Model ID')->badge(),

                        TextEntry::make('collection_name')->label('Kolekcja')->badge(),
                        TextEntry::make('disk')->label('Dysk')->badge(),

                        TextEntry::make('name')->label('Nazwa'),
                        TextEntry::make('file_name')->label('Nazwa pliku')->copyable(),

                        TextEntry::make('mime_type')->label('MIME Type')->placeholder('Brak'),
                        TextEntry::make('size')->label('Rozmiar')->numeric()->suffix(' B'),

                        TextEntry::make('order_column')->label('Kolejnosc')->placeholder('Brak'),
                        TextEntry::make('created_at')->label('Dodano')->dateTime('d.m.Y H:i'),
                        TextEntry::make('updated_at')->label('Aktualizacja')->since(),
                    ]),
            ]);
    }
}
