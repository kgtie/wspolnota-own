<?php

namespace App\Filament\SuperAdmin\Resources\Media\Schemas;

use App\Filament\SuperAdmin\Resources\Media\MediaResource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Docelowy rekord i plik')
                    ->columns(2)
                    ->schema([
                        Select::make('model_type')
                            ->label('Model docelowy')
                            ->required()
                            ->native(false)
                            ->options(MediaResource::getAttachableModelOptions()),

                        TextInput::make('model_id')
                            ->label('ID rekordu modelu')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Podaj ID istniejacego rekordu modelu docelowego.'),

                        TextInput::make('collection_name')
                            ->label('Kolekcja')
                            ->required()
                            ->default('attachments')
                            ->maxLength(255),

                        Select::make('disk')
                            ->label('Dysk')
                            ->placeholder('Domyslny dla kolekcji')
                            ->native(false)
                            ->options(static::diskOptions())
                            ->default(null),

                        FileUpload::make('uploaded_file')
                            ->label('Plik')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->storeFiles(false)
                            ->maxSize(102400)
                            ->columnSpanFull(),
                    ]),

                Section::make('Metadane')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nazwa')
                            ->required(fn (string $operation): bool => $operation !== 'create')
                            ->maxLength(255),

                        TextInput::make('file_name')
                            ->label('Nazwa pliku')
                            ->required(fn (string $operation): bool => $operation !== 'create')
                            ->maxLength(255),

                        TextInput::make('mime_type')
                            ->label('MIME Type')
                            ->maxLength(255),

                        TextInput::make('order_column')
                            ->label('Kolejnosc')
                            ->numeric()
                            ->minValue(1),

                        KeyValue::make('custom_properties')
                            ->label('Custom properties')
                            ->keyLabel('Klucz')
                            ->valueLabel('Wartosc')
                            ->addActionLabel('Dodaj wpis')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function diskOptions(): array
    {
        return collect(config('filesystems.disks', []))
            ->keys()
            ->mapWithKeys(fn (string $disk): array => [$disk => $disk])
            ->all();
    }
}
