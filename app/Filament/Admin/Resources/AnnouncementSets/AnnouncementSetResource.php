<?php

namespace App\Filament\Admin\Resources\AnnouncementSets;

use App\Filament\Admin\Resources\AnnouncementSets\Pages;
use App\Filament\Admin\Resources\AnnouncementSets\RelationManagers\AnnouncementsRelationManager;
use App\Models\AnnouncementSet;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AnnouncementSetPdfService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementSetResource extends Resource
{
    protected static ?string $model = AnnouncementSet::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Parafia';
    }

    public static function getNavigationLabel(): string
    {
        return 'Ogłoszenia';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-megaphone';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Tytuł zestawu')
                ->required()
                ->maxLength(255),

            DatePicker::make('valid_from')
                ->label('Obowiązuje od')
                ->required(),

            DatePicker::make('valid_until')
                ->label('Obowiązuje do')
                ->required()
                ->afterOrEqual('valid_from'),

            // Tylko podgląd (status i AI) – w Adminie status zmieniamy akcjami Publish/Unpublish/Archive
            Placeholder::make('status_label')
                ->label('Status')
                ->content(fn (?AnnouncementSet $record) => $record?->status_label ?? 'Szkic'),

            Placeholder::make('ai_summary')
                ->label('Streszczenie AI')
                ->content(fn (?AnnouncementSet $record) => $record?->ai_summary ?: 'Brak (wygeneruje się automatycznie)'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('valid_from', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable(),

                TextColumn::make('valid_period')
                    ->label('Okres'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => AnnouncementSet::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state) => AnnouncementSet::getStatusColor($state))
                    ->icon(fn (string $state) => AnnouncementSet::getStatusIcon($state)),

                TextColumn::make('announcements_count')
                    ->label('Punktów')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AnnouncementSet::getStatusOptions()),
            ])
            ->recordActions([
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function (AnnouncementSet $record, AnnouncementSetPdfService $pdf): StreamedResponse {
                        $filename = 'ogloszenia_'.$record->id.'.pdf';

                        return response()->streamDownload(
                            fn () => print($pdf->make($record)->output()),
                            $filename,
                            ['Content-Type' => 'application/pdf']
                        );
                    }),
                
                \Filament\Actions\EditAction::make(),

                Action::make('publish')
                    ->label('Opublikuj')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AnnouncementSet $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(fn (AnnouncementSet $record) => $record->publish(auth()->id())),

                Action::make('unpublish')
                    ->label('Cofnij do szkicu')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (AnnouncementSet $record) => $record->status === 'published')
                    ->requiresConfirmation()
                    ->action(fn (AnnouncementSet $record) => $record->unpublish()),

                Action::make('archive')
                    ->label('Archiwizuj')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn (AnnouncementSet $record) => $record->status !== 'archived')
                    ->requiresConfirmation()
                    ->action(fn (AnnouncementSet $record) => $record->archive()),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where('parish_id', $tenant->id)
            ->withCount('announcements'); // <- żeby announcements_count działało i sortowało
    }

    public static function getRelations(): array
    {
        return [
            AnnouncementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncementSets::route('/'),
            'create' => Pages\CreateAnnouncementSet::route('/create'),
            'edit' => Pages\EditAnnouncementSet::route('/{record}/edit'),
        ];
    }
}
