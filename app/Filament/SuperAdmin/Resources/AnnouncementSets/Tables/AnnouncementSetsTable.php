<?php

namespace App\Filament\SuperAdmin\Resources\AnnouncementSets\Tables;

use App\Models\AnnouncementSet;
use App\Models\Parish;
use App\Models\User;
use App\Support\Announcements\AnnouncementSetPdfExporter;
use App\Support\Announcements\AnnouncementSetSummarizer;
use App\Support\Notifications\ParishAudienceResolver;
use App\Support\SuperAdmin\InstantCommunicationService;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

                TextColumn::make('parish.name')
                    ->label('Parafia')
                    ->searchable()
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

                TextColumn::make('push_notification_sent_at')
                    ->label('Push dispatch')
                    ->state(fn (AnnouncementSet $record): string => $record->push_notification_sent_at
                        ? 'Wyslano '.$record->push_notification_sent_at->format('d.m H:i')
                        : 'Oczekuje')
                    ->badge()
                    ->color(fn (AnnouncementSet $record): string => $record->push_notification_sent_at ? 'success' : 'warning')
                    ->toggleable(),

                TextColumn::make('email_notification_sent_at')
                    ->label('Email dispatch')
                    ->state(fn (AnnouncementSet $record): string => $record->email_notification_sent_at
                        ? 'Wyslano '.$record->email_notification_sent_at->format('d.m H:i')
                        : 'Oczekuje')
                    ->badge()
                    ->color(fn (AnnouncementSet $record): string => $record->email_notification_sent_at ? 'success' : 'warning')
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

                SelectFilter::make('parish_id')
                    ->label('Parafia')
                    ->options(fn (): array => Parish::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    self::sendInstantPushAction(),
                    self::sendInstantEmailAction(),
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
                    self::sendInstantPushBulkAction(),
                    self::sendInstantEmailBulkAction(),
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

                if ($admin instanceof User) {
                    activity('admin-announcement-management')
                        ->causedBy($admin)
                        ->performedOn($record)
                        ->event('announcement_set_status_updated')
                        ->withProperties([
                            'parish_id' => $record->parish_id,
                            'announcement_set_id' => $record->getKey(),
                            'target_status' => $status,
                        ])
                        ->log('Proboszcz zaktualizowal status zestawu ogloszen.');
                }
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
                $admin = Filament::auth()->user();
                $exporter = app(AnnouncementSetPdfExporter::class);

                if (! $exporter->hasPrintableItems($record)) {
                    Notification::make()
                        ->warning()
                        ->title('Brak aktywnych ogloszen do wydruku.')
                        ->send();

                    return null;
                }

                if ($admin instanceof User) {
                    activity('admin-announcement-management')
                        ->causedBy($admin)
                        ->performedOn($record)
                        ->event('announcement_set_pdf_exported')
                        ->withProperties([
                            'parish_id' => $record->parish_id,
                            'announcement_set_id' => $record->getKey(),
                            'active_items_count' => $record->items()->where('is_active', true)->count(),
                        ])
                        ->log('Proboszcz wygenerowal PDF z ogloszeniami parafialnymi.');
                }

                return $exporter->download($record);
            });
    }

    protected static function sendInstantPushAction(): Action
    {
        return Action::make('send_announcement_push_now')
            ->label('Push teraz')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('info')
            ->visible(fn (AnnouncementSet $record): bool => $record->status === 'published')
            ->schema([
                TextInput::make('title')
                    ->label('Tytul')
                    ->required()
                    ->maxLength(120)
                    ->default(fn (AnnouncementSet $record): string => 'Nowy pakiet ogloszen'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(5)
                    ->maxLength(1000)
                    ->default(fn (AnnouncementSet $record): string => 'Opublikowano nowy pakiet ogloszen: '.$record->title),
            ])
            ->action(function (
                AnnouncementSet $record,
                array $data,
                InstantCommunicationService $service,
                ParishAudienceResolver $audiences,
            ): void {
                $users = $audiences->homeParishUsers((int) $record->parish_id, withDevices: true);

                $result = $service->queuePushToUsers(
                    users: $users,
                    title: (string) $data['title'],
                    body: (string) $data['body'],
                    type: 'ANNOUNCEMENTS_PACKAGE_PUBLISHED',
                    routingData: [
                        'announcement_set_id' => (string) $record->getKey(),
                        'parish_id' => (string) $record->parish_id,
                        'source' => 'superadmin_manual',
                    ],
                    preferenceTopic: 'announcements',
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano push dla ogloszen.')
                    ->body("Uzytkownicy: {$result['users']} · urzadzenia: {$result['devices']} · skipped: {$result['skipped']}")
                    ->send();
            });
    }

    protected static function sendInstantEmailAction(): Action
    {
        return Action::make('send_announcement_email_now')
            ->label('Email teraz')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->visible(fn (AnnouncementSet $record): bool => $record->status === 'published')
            ->schema([
                TextInput::make('subject')
                    ->label('Temat')
                    ->required()
                    ->maxLength(200)
                    ->default(fn (AnnouncementSet $record): string => 'Nowy pakiet ogloszen: '.$record->title),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(8)
                    ->maxLength(12000)
                    ->default(fn (AnnouncementSet $record): string => 'Opublikowano nowy pakiet ogloszen w parafii: '.$record->title),
            ])
            ->action(function (
                AnnouncementSet $record,
                array $data,
                InstantCommunicationService $service,
                ParishAudienceResolver $audiences,
            ): void {
                $actor = Filament::auth()->user();
                $users = $audiences->homeParishUsers((int) $record->parish_id);

                $result = $service->sendEmailToUsers(
                    users: $users,
                    subjectLine: (string) $data['subject'],
                    messageBody: (string) $data['body'],
                    actor: $actor instanceof User ? $actor : null,
                    options: ['preference_topic' => 'announcements'],
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano email dla ogloszen.')
                    ->body("Odbiorcy: {$result['users']} · queued: {$result['queued']} · skipped: {$result['skipped']}")
                    ->send();
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
                $admin = Filament::auth()->user();
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

                    if ($admin instanceof User) {
                        activity('admin-announcement-management')
                            ->causedBy($admin)
                            ->performedOn($record)
                            ->event('announcement_set_ai_summary_generated')
                            ->withProperties([
                                'parish_id' => $record->parish_id,
                                'announcement_set_id' => $record->getKey(),
                                'summary_length' => mb_strlen($summary),
                                'model' => (string) config('gemini.model'),
                            ])
                            ->log('Proboszcz recznie wygenerowal streszczenie AI dla zestawu ogloszen.');
                    }

                    Notification::make()
                        ->success()
                        ->title('Wygenerowano streszczenie AI.')
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    if ($admin instanceof User) {
                        activity('admin-announcement-management')
                            ->causedBy($admin)
                            ->performedOn($record)
                            ->event('announcement_set_ai_summary_generation_failed')
                            ->withProperties([
                                'parish_id' => $record->parish_id,
                                'announcement_set_id' => $record->getKey(),
                                'error' => $exception->getMessage(),
                            ])
                            ->log('Reczne generowanie streszczenia AI dla zestawu ogloszen zakonczone bledem.');
                    }

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

                if ($admin instanceof User) {
                    activity('admin-announcement-management')
                        ->causedBy($admin)
                        ->performedOn($clone)
                        ->event('announcement_set_duplicated')
                        ->withProperties([
                            'parish_id' => $clone->parish_id,
                            'source_set_id' => $record->getKey(),
                            'new_set_id' => $clone->getKey(),
                            'copied_items_count' => $items->count(),
                        ])
                        ->log('Proboszcz zduplikowal zestaw ogloszen.');
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
                $updatedIds = [];
                $selectedCount = is_countable($records) ? count($records) : 0;

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
                    $updatedIds[] = $record->getKey();
                }

                if ($admin instanceof User && $updated > 0) {
                    $firstUpdatedSet = $records
                        ->first(fn ($record): bool => $record instanceof AnnouncementSet && in_array($record->getKey(), $updatedIds, true));

                    activity('admin-announcement-management')
                        ->causedBy($admin)
                        ->event('announcement_sets_bulk_status_updated')
                        ->withProperties([
                            'parish_id' => $firstUpdatedSet instanceof AnnouncementSet ? $firstUpdatedSet->parish_id : null,
                            'target_status' => $status,
                            'selected_count' => $selectedCount,
                            'updated_count' => $updated,
                            'updated_set_ids' => $updatedIds,
                        ])
                        ->log('Proboszcz masowo zaktualizowal statusy zestawow ogloszen.');
                }

                Notification::make()
                    ->success()
                    ->title('Zaktualizowano statusy zestawow ogloszen.')
                    ->body("Liczba zmienionych rekordow: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function sendInstantPushBulkAction(): BulkAction
    {
        return BulkAction::make('send_announcement_push_bulk')
            ->label('Push teraz')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('info')
            ->schema([
                TextInput::make('title')
                    ->label('Tytul')
                    ->required()
                    ->maxLength(120)
                    ->default('Nowe informacje z wybranych parafii'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(5)
                    ->maxLength(1000),
            ])
            ->action(function (
                $records,
                array $data,
                InstantCommunicationService $service,
                ParishAudienceResolver $audiences,
            ): void {
                $sets = collect($records)
                    ->filter(fn ($record): bool => $record instanceof AnnouncementSet && $record->status === 'published')
                    ->values();

                $parishIds = $sets->pluck('parish_id')->filter()->unique()->values();

                $users = $audiences->homeParishUsers($parishIds, withDevices: true);

                $result = $service->queuePushToUsers(
                    users: $users,
                    title: (string) $data['title'],
                    body: (string) $data['body'],
                    type: 'MANUAL_MESSAGE',
                    routingData: [
                        'scope' => 'announcement_sets_bulk',
                        'announcement_set_ids' => json_encode($sets->map(fn (AnnouncementSet $record): int => (int) $record->getKey())->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'parish_ids' => json_encode($parishIds->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'source' => 'superadmin_bulk',
                    ],
                    preferenceTopic: 'announcements',
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano push dla zaznaczonych ogloszen.')
                    ->body("Uzytkownicy: {$result['users']} · urzadzenia: {$result['devices']} · skipped: {$result['skipped']}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function sendInstantEmailBulkAction(): BulkAction
    {
        return BulkAction::make('send_announcement_email_bulk')
            ->label('Email teraz')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->schema([
                TextInput::make('subject')
                    ->label('Temat')
                    ->required()
                    ->maxLength(200)
                    ->default('Nowe informacje z wybranych parafii'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(8)
                    ->maxLength(12000),
            ])
            ->action(function (
                $records,
                array $data,
                InstantCommunicationService $service,
                ParishAudienceResolver $audiences,
            ): void {
                $actor = Filament::auth()->user();
                $parishIds = collect($records)
                    ->filter(fn ($record): bool => $record instanceof AnnouncementSet && $record->status === 'published')
                    ->pluck('parish_id')
                    ->filter()
                    ->unique()
                    ->values();

                $users = $audiences->homeParishUsers($parishIds);

                $result = $service->sendEmailToUsers(
                    users: $users,
                    subjectLine: (string) $data['subject'],
                    messageBody: (string) $data['body'],
                    actor: $actor instanceof User ? $actor : null,
                    options: ['preference_topic' => 'announcements'],
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano email dla zaznaczonych ogloszen.')
                    ->body("Odbiorcy: {$result['users']} · queued: {$result['queued']} · skipped: {$result['skipped']}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
