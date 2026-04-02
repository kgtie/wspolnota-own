<?php

namespace App\Filament\SuperAdmin\Resources\Media\Tables;

use App\Filament\SuperAdmin\Resources\Media\MediaResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                ImageColumn::make('preview')
                    ->label('Podgląd')
                    ->circular()
                    ->imageSize(42)
                    ->state(fn (Media $record): ?string => str_starts_with((string) ($record->mime_type ?? ''), 'image/')
                        ? $record->getUrl('thumb')
                        : null),

                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->sortable(),

                TextColumn::make('model_type')
                    ->label('Model')
                    ->badge()
                    ->state(fn (Media $record): string => MediaResource::resolveModelTypeLabel($record->model_type))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model_id')
                    ->label('Model ID')
                    ->badge()
                    ->sortable(),

                TextColumn::make('collection_name')
                    ->label('Kolekcja')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('file_name')
                    ->label('Plik')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('disk')
                    ->label('Dysk')
                    ->badge()
                    ->sortable(),

                TextColumn::make('mime_type')
                    ->label('MIME')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('size')
                    ->label('Rozmiar')
                    ->numeric()
                    ->suffix(' B')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dodano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('model_type')
                    ->label('Model')
                    ->options(fn (): array => Media::query()
                        ->select('model_type')
                        ->distinct()
                        ->orderBy('model_type')
                        ->pluck('model_type', 'model_type')
                        ->mapWithKeys(fn (string $modelType): array => [
                            $modelType => MediaResource::resolveModelTypeLabel($modelType),
                        ])
                        ->all()),

                SelectFilter::make('collection_name')
                    ->label('Kolekcja')
                    ->options(fn (): array => Media::query()
                        ->select('collection_name')
                        ->distinct()
                        ->orderBy('collection_name')
                        ->pluck('collection_name', 'collection_name')
                        ->all()),

                SelectFilter::make('disk')
                    ->label('Dysk')
                    ->options(fn (): array => Media::query()
                        ->select('disk')
                        ->distinct()
                        ->orderBy('disk')
                        ->pluck('disk', 'disk')
                        ->all()),

                Filter::make('images_only')
                    ->label('Tylko obrazy')
                    ->query(fn (Builder $query): Builder => $query->where('mime_type', 'like', 'image/%')),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('open_file')
                        ->label('Otwórz plik')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Media $record): string => $record->getFullUrl())
                        ->openUrlInNewTab(),
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
