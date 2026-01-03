<?php

namespace App\Filament\Superadmin\Resources\AnnouncementSets;

use App\Filament\Superadmin\Resources\AnnouncementSets\Pages;
use App\Filament\Superadmin\Resources\AnnouncementSets\RelationManagers\AnnouncementsRelationManager;
use App\Jobs\GenerateAnnouncementSetAiSummaryJob;
use App\Models\AnnouncementSet;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
        return 'System';
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
            Select::make('parish_id')
                ->label('Parafia')
                ->relationship('parish', 'name')
                ->searchable()
                ->required(),

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

            Placeholder::make('status_label')
                ->label('Status')
                ->content(fn (?AnnouncementSet $record) => $record?->status_label ?? 'Szkic'),

            Placeholder::make('ai_summary')
                ->label('Streszczenie AI')
                ->content(fn (?AnnouncementSet $record) => $record?->ai_summary ?: 'Brak'),

            Placeholder::make('ai_summary_generated_at')
                ->label('Wygenerowano')
                ->content(fn (?AnnouncementSet $record) => $record?->ai_summary_generated_at?->format('d.m.Y H:i') ?: '—'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('valid_from', 'desc')
            ->columns([
                TextColumn::make('parish.name')
                    ->label('Parafia')
                    ->searchable(),

                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => AnnouncementSet::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state) => AnnouncementSet::getStatusColor($state))
                    ->icon(fn (string $state) => AnnouncementSet::getStatusIcon($state)),

                TextColumn::make('announcements_count')
                    ->label('Punktów')
                    ->sortable(),

                TextColumn::make('ai_summary_generated_at')
                    ->label('AI')
                    ->formatStateUsing(fn ($state) => $state ? '✅' : 'none'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AnnouncementSet::getStatusOptions()),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),

                Action::make('generate_ai')
                    ->label('Generuj AI')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (AnnouncementSet $record) {
                        GenerateAnnouncementSetAiSummaryJob::dispatch($record->id, true);

                        Notification::make()
                            ->title('Zlecono generowanie streszczenia AI')
                            ->body('Zadanie zostało dodane do kolejki.')
                            ->success()
                            ->send();
                    }),

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
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('announcements');
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
