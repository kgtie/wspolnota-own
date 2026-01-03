<?php

namespace App\Filament\Admin\Resources\NewsPosts\RelationManagers;

use App\Models\MediaFile;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Pliki';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('collection')
                ->label('Kolekcja')
                ->options([
                    'attachments' => 'Załączniki',
                    'cover' => 'Okładka',
                ])
                ->default('attachments')
                ->required(),

            FileUpload::make('path')
                ->label('Plik')
                ->required()
                ->disk('public')
                ->directory('news')
                ->preserveFilenames(false)
                ->getUploadedFileNameForStorageUsing(function (UploadedFile $file): string {
                    return Str::random(24).'.'.$file->getClientOriginalExtension();
                })
                ->afterStateUpdated(function ($state, callable $set) {
                    // $state = ścieżka na dysku
                    $set('disk', 'public');
                    $set('file_name', basename((string) $state));
                }),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('collection')->label('Kolekcja')->badge(),
                TextColumn::make('original_name')->label('Nazwa')->wrap(),
                TextColumn::make('mime_type')->label('MIME')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('size')->label('Rozmiar')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dodano')->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Uzupełnij metadane w DB
                        $data['uploader_user_id'] = auth()->id();
                        $data['original_name'] = $data['original_name'] ?? $data['file_name'] ?? 'plik';
                        $data['mime_type'] = $data['mime_type'] ?? null;
                        $data['size'] = $data['size'] ?? null;
                        $data['visibility'] = $data['visibility'] ?? 'public';

                        // Jeśli FileUpload nie ustawiło tego automatycznie:
                        $data['disk'] = $data['disk'] ?? 'public';

                        return $data;
                    }),
            ]);
    }
}
