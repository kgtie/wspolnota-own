<?php

namespace App\Filament\Admin\Resources\AnnouncementSets\Tables;

use App\Models\AnnouncementSet;
use App\Models\User;
use App\Support\Announcements\AnnouncementSetPdfExporter;
use App\Support\Announcements\AnnouncementSetSummarizer;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Throwable;

class AnnouncementSetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('effective_from', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('effective_from')
                    ->label('Od')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('effective_to')
                    ->label('Do')
                    ->date('d.m.Y')
                    ->placeholder('Brak')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Nazwa zestawu')
                    ->searchable()
                    ->sortable()
                    ->description(fn (AnnouncementSet $record): ?string => $record->week_label ?: null),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'archived' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => AnnouncementSet::getStatusOptions()[$state] ?? $state),

                TextColumn::make('items_count')
                    ->label('Ogloszenia')
                    ->state(fn (AnnouncementSet $record): string => (string) ($record->items_count ?? 0))
                    ->badge()
                    ->sortable()
                    ->color('info'),

                TextColumn::make('important_items_count')
                    ->label('Wazne')
                    ->state(fn (AnnouncementSet $record): string => (string) ($record->important_items_count ?? 0))
                    ->badge()
                    ->sortable()
                    ->color(fn (AnnouncementSet $record): string => (($record->important_items_count ?? 0) > 0) ? 'danger' : 'gray'),

                TextColumn::make('active_items_count')
                    ->label('Aktywne')
                    ->state(fn (AnnouncementSet $record): string => (string) ($record->active_items_count ?? 0))
                    ->badge()
                    ->sortable()
                    ->color(fn (AnnouncementSet $record): string => (($record->active_items_count ?? 0) > 0) ? 'success' : 'gray')
                    ->toggleable(),

                TextColumn::make('summary_ai')
                    ->label('AI')
                    ->state(fn (AnnouncementSet $record): string => filled($record->summary_ai) ? 'Gotowe' : 'Brak')
                    ->badge()
                    ->color(fn (AnnouncementSet $record): string => filled($record->summary_ai) ? 'success' : 'warning')
                    ->toggleable(),

                TextColumn::make('notifications_sent_at')
                    ->label('Mail do parafian')
                    ->state(fn (AnnouncementSet $record): string => $record->notifications_sent_at
                        ? 'Wyslano ('.$record->notifications_sent_at->format('d.m H:i').')'
                        : 'Nie wyslano')
                    ->badge()
                    ->color(fn (AnnouncementSet $record): string => $record->notifications_sent_at ? 'success' : 'warning')
                    ->toggleable(),

                TextColumn::make('published_at')
                    ->label('Opublikowano')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updatedBy.full_name')
                    ->label('Edytowal')
                    ->placeholder('Brak')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('current')
                    ->label('Aktualne')
                    ->query(fn (Builder $query): Builder => $query->current()),

                Filter::make('future')
                    ->label('Przyszle')
                    ->query(fn (Builder $query): Builder => $query->whereDate('effective_from', '>', now()->toDateString())),

                Filter::make('past')
                    ->label('Przeszle')
                    ->query(fn (Builder $query): Builder => $query->whereDate('effective_to', '<', now()->toDateString())),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AnnouncementSet::getStatusOptions()),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    self::setStatusAction('published', 'Opublikuj', 'heroicon-o-check-circle', 'success'),
                    self::setStatusAction('draft', 'Ustaw jako szkic', 'heroicon-o-document-text', 'warning'),
                    self::setStatusAction('archived', 'Archiwizuj', 'heroicon-o-archive-box', 'gray'),
                    self::generateSummaryAction(),
                    self::printAction(),
                    self::duplicateAction(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::bulkSetStatusAction('published', 'Oznacz zaznaczone jako opublikowane', 'heroicon-o-check-circle', 'success'),
                    self::bulkSetStatusAction('draft', 'Oznacz zaznaczone jako szkice', 'heroicon-o-document-text', 'warning'),
                    self::bulkSetStatusAction('archived', 'Oznacz zaznaczone jako archiwalne', 'heroicon-o-archive-box', 'gray'),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }

    protected static function setStatusAction(string $status, string $label, string $icon, string $color): Action
    {
        return Action::make("set_status_{$status}")
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (AnnouncementSet $record): bool => $record->status !== $status)
            ->requiresConfirmation()
            ->action(function (AnnouncementSet $record) use ($status): void {
                $admin = Filament::auth()->user();

                $payload = [
                    'status' => $status,
                    'updated_by_user_id' => $admin instanceof User ? $admin->id : $record->updated_by_user_id,
                ];

                if ($status === 'published') {
                    $payload['published_at'] = $record->published_at ?? now();
                }

                if ($status === 'draft') {
                    $payload['published_at'] = null;
                }

                $record->update($payload);
            })
            ->successNotificationTitle('Status zestawu zostal zaktualizowany.');
    }

    protected static function printAction(): Action
    {
        return Action::make('print_pdf')
            ->label('Wydruk PDF')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->action(function (AnnouncementSet $record) {
                $exporter = app(AnnouncementSetPdfExporter::class);

                if (! $exporter->hasPrintableItems($record)) {
                    Notification::make()
                        ->warning()
                        ->title('Brak aktywnych ogloszen do wydruku.')
                        ->send();

                    return null;
                }

                return $exporter->download($record);
            });
    }

    protected static function generateSummaryAction(): Action
    {
        return Action::make('generate_ai_summary')
            ->label(fn (AnnouncementSet $record): string => filled($record->summary_ai) ? 'Generuj ponownie AI' : 'Generuj streszczenie AI')
            ->icon('heroicon-o-sparkles')
            ->color('info')
            ->visible(fn (AnnouncementSet $record): bool => $record->status === 'published')
            ->requiresConfirmation()
            ->action(function (AnnouncementSet $record): void {
                $summarizer = app(AnnouncementSetSummarizer::class);

                if (! $summarizer->canGenerateForSet($record)) {
                    Notification::make()
                        ->warning()
                        ->title('Nie mozna wygenerowac streszczenia.')
                        ->body('Zestaw musi byc opublikowany i zawierac co najmniej jedno aktywne ogloszenie.')
                        ->send();

                    return;
                }

                try {
                    $summary = $summarizer->summarize($record);

                    $record->update([
                        'summary_ai' => $summary,
                        'summary_generated_at' => now(),
                        'summary_model' => (string) config('gemini.model'),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Wygenerowano streszczenie AI.')
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title('Nie udalo sie wygenerowac streszczenia AI.')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }

    protected static function duplicateAction(): Action
    {
        return Action::make('duplicate_set')
            ->label('Duplikuj zestaw')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->action(function (AnnouncementSet $record): void {
                $admin = Filament::auth()->user();

                $clone = $record->replicate();
                $clone->title = $record->title.' (kopia)';
                $clone->status = 'draft';
                $clone->published_at = null;
                $clone->effective_from = $record->effective_from?->copy()->addWeek();
                $clone->effective_to = $record->effective_to?->copy()->addWeek();
                $clone->created_by_user_id = $admin instanceof User ? $admin->id : $record->created_by_user_id;
                $clone->updated_by_user_id = null;
                $clone->save();

                $items = $record->items()->orderBy('position')->get();

                foreach ($items as $item) {
                    $clone->items()->create([
                        'position' => $item->position,
                        'title' => $item->title,
                        'content' => $item->content,
                        'is_important' => $item->is_important,
                        'is_active' => $item->is_active,
                        'created_by_user_id' => $admin instanceof User ? $admin->id : $item->created_by_user_id,
                        'updated_by_user_id' => null,
                    ]);
                }

                Notification::make()
                    ->success()
                    ->title('Utworzono kopie zestawu ogloszen.')
                    ->body('Skopiowano wszystkie pojedyncze ogloszenia i przesunieto daty o 7 dni.')
                    ->send();
            });
    }

    protected static function bulkSetStatusAction(string $status, string $label, string $icon, string $color): BulkAction
    {
        return BulkAction::make("bulk_set_status_{$status}")
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->action(function ($records) use ($status): void {
                $admin = Filament::auth()->user();
                $updated = 0;

                foreach ($records as $record) {
                    if (! $record instanceof AnnouncementSet || $record->status === $status) {
                        continue;
                    }

                    $payload = [
                        'status' => $status,
                        'updated_by_user_id' => $admin instanceof User ? $admin->id : $record->updated_by_user_id,
                    ];

                    if ($status === 'published') {
                        $payload['published_at'] = $record->published_at ?? now();
                    }

                    if ($status === 'draft') {
                        $payload['published_at'] = null;
                    }

                    $record->update($payload);
                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title('Zaktualizowano statusy zestawow ogloszen.')
                    ->body("Liczba zmienionych rekordow: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
