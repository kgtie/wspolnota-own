<?php

namespace App\Filament\SuperAdmin\Resources\NewsPosts\Tables;

use App\Filament\SuperAdmin\Resources\NewsComments\NewsCommentResource;
use App\Models\NewsPost;
use App\Models\Parish;
use App\Models\User;
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
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NewsPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label('Okladka')
                    ->collection('featured_image')
                    ->conversion('thumb')
                    ->height(52)
                    ->width(80),

                TextColumn::make('title')
                    ->label('Tytul')
                    ->searchable()
                    ->sortable()
                    ->description(fn (NewsPost $record): ?string => filled($record->content)
                        ? (string) str(strip_tags((string) $record->content))->squish()->limit(90)
                        : null),

                TextColumn::make('parish.name')
                    ->label('Parafia')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => NewsPost::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => self::statusColor($state)),

                TextColumn::make('is_pinned')
                    ->label('Przypiety')
                    ->state(fn (NewsPost $record): string => $record->is_pinned ? 'Tak' : 'Nie')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Tak' ? 'info' : 'gray')
                    ->sortable(),

                TextColumn::make('scheduled_for')
                    ->label('Zaplanowano')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('published_at')
                    ->label('Opublikowano')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('comments_count')
                    ->label('Komentarze')
                    ->badge()
                    ->state(fn (NewsPost $record): string => (string) ($record->comments_count ?? 0))
                    ->color('info')
                    ->url(fn (NewsPost $record): string => NewsCommentResource::getUrl('index', [
                        'filters' => [
                            'news_post_id' => ['value' => $record->getKey()],
                        ],
                    ])),

                TextColumn::make('push_notification_sent_at')
                    ->label('Push dispatch')
                    ->state(fn (NewsPost $record): string => $record->push_notification_sent_at
                        ? 'Wyslano '.$record->push_notification_sent_at->format('d.m H:i')
                        : 'Oczekuje')
                    ->badge()
                    ->color(fn (NewsPost $record): string => $record->push_notification_sent_at ? 'success' : 'warning')
                    ->toggleable(),

                TextColumn::make('email_notification_sent_at')
                    ->label('Email dispatch')
                    ->state(fn (NewsPost $record): string => $record->email_notification_sent_at
                        ? 'Wyslano '.$record->email_notification_sent_at->format('d.m H:i')
                        : 'Oczekuje')
                    ->badge()
                    ->color(fn (NewsPost $record): string => $record->email_notification_sent_at ? 'success' : 'warning')
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
                Filter::make('published')
                    ->label('Opublikowane teraz')
                    ->query(fn (Builder $query): Builder => $query->published()),

                Filter::make('scheduled_only')
                    ->label('Tylko zaplanowane')
                    ->query(fn (Builder $query): Builder => $query->scheduled()),

                Filter::make('draft_only')
                    ->label('Tylko szkice')
                    ->query(fn (Builder $query): Builder => $query->draft()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(NewsPost::getStatusOptions()),

                SelectFilter::make('parish_id')
                    ->label('Parafia')
                    ->options(fn (): array => Parish::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),

                TernaryFilter::make('is_pinned')
                    ->label('Przypiety')
                    ->nullable()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('comments')
                        ->label('Komentarze')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->url(fn (NewsPost $record): string => NewsCommentResource::getUrl('index', [
                            'filters' => [
                                'news_post_id' => ['value' => $record->getKey()],
                            ],
                        ])),
                    self::sendInstantPushAction(),
                    self::sendInstantEmailAction(),
                    self::setStatusAction('published', 'Opublikuj', 'heroicon-o-check-circle', 'success'),
                    self::setStatusAction('scheduled', 'Zaplanuj', 'heroicon-o-clock', 'warning'),
                    self::setStatusAction('draft', 'Ustaw jako szkic', 'heroicon-o-document-text', 'info'),
                    self::setStatusAction('archived', 'Archiwizuj', 'heroicon-o-archive-box', 'gray'),
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
                    self::bulkSetStatusAction('scheduled', 'Oznacz zaznaczone jako zaplanowane', 'heroicon-o-clock', 'warning'),
                    self::bulkSetStatusAction('draft', 'Oznacz zaznaczone jako szkice', 'heroicon-o-document-text', 'info'),
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
            ->visible(fn (NewsPost $record): bool => $record->status !== $status)
            ->requiresConfirmation()
            ->action(function (NewsPost $record) use ($status): void {
                $admin = Filament::auth()->user();

                $record->update(array_merge(
                    self::resolveStatusPayload($status, $record),
                    [
                        'updated_by_user_id' => $admin instanceof User ? $admin->id : $record->updated_by_user_id,
                    ],
                ));
            })
            ->successNotificationTitle('Status wpisu zostal zaktualizowany.');
    }

    protected static function duplicateAction(): Action
    {
        return Action::make('duplicate_post')
            ->label('Duplikuj wpis')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->action(function (NewsPost $record): void {
                $admin = Filament::auth()->user();

                $clone = $record->replicate();
                $clone->title = $record->title.' (kopia)';
                $clone->slug = '';
                $clone->status = 'draft';
                $clone->published_at = null;
                $clone->scheduled_for = null;
                $clone->is_pinned = false;
                $clone->created_by_user_id = $admin instanceof User ? $admin->id : $record->created_by_user_id;
                $clone->updated_by_user_id = null;
                $clone->save();

                Notification::make()
                    ->success()
                    ->title('Utworzono kopie wpisu.')
                    ->body('Kopia ma status szkicu i moze byc dalej edytowana.')
                    ->send();
            });
    }

    protected static function sendInstantPushAction(): Action
    {
        return Action::make('send_news_push_now')
            ->label('Push teraz')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('info')
            ->visible(fn (NewsPost $record): bool => $record->status === 'published')
            ->schema([
                TextInput::make('title')
                    ->label('Tytul')
                    ->required()
                    ->maxLength(120)
                    ->default(fn (NewsPost $record): string => 'Nowa aktualnosc w parafii'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(5)
                    ->maxLength(1000)
                    ->default(fn (NewsPost $record): string => 'Dodano nowa aktualnosc: '.$record->title),
            ])
            ->action(function (
                NewsPost $record,
                array $data,
                InstantCommunicationService $service,
                ParishAudienceResolver $audiences,
            ): void {
                $users = $audiences->homeParishUsers((int) $record->parish_id, withDevices: true);

                $result = $service->queuePushToUsers(
                    users: $users,
                    title: (string) $data['title'],
                    body: (string) $data['body'],
                    type: 'NEWS_CREATED',
                    routingData: [
                        'news_id' => (string) $record->getKey(),
                        'parish_id' => (string) $record->parish_id,
                        'source' => 'superadmin_manual',
                    ],
                    preferenceTopic: 'news',
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano push dla aktualnosci.')
                    ->body("Uzytkownicy: {$result['users']} · urzadzenia: {$result['devices']} · skipped: {$result['skipped']}")
                    ->send();
            });
    }

    protected static function sendInstantEmailAction(): Action
    {
        return Action::make('send_news_email_now')
            ->label('Email teraz')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->visible(fn (NewsPost $record): bool => $record->status === 'published')
            ->schema([
                TextInput::make('subject')
                    ->label('Temat')
                    ->required()
                    ->maxLength(200)
                    ->default(fn (NewsPost $record): string => 'Nowa aktualnosc: '.$record->title),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(8)
                    ->maxLength(12000)
                    ->default(fn (NewsPost $record): string => 'Opublikowano nowa aktualnosc w parafii: '.$record->title),
            ])
            ->action(function (
                NewsPost $record,
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
                    options: ['preference_topic' => 'news'],
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano email dla aktualnosci.')
                    ->body("Odbiorcy: {$result['users']} · queued: {$result['queued']} · skipped: {$result['skipped']}")
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
                $selectedCount = is_countable($records) ? count($records) : 0;

                foreach ($records as $record) {
                    if (! $record instanceof NewsPost || $record->status === $status) {
                        continue;
                    }

                    $record->update(array_merge(
                        self::resolveStatusPayload($status, $record),
                        [
                            'updated_by_user_id' => $admin instanceof User ? $admin->id : $record->updated_by_user_id,
                        ],
                    ));

                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title('Zaktualizowano status wpisow.')
                    ->body("Zmieniono {$updated} z {$selectedCount} zaznaczonych rekordow.")
                    ->send();
            });
    }

    protected static function sendInstantPushBulkAction(): BulkAction
    {
        return BulkAction::make('send_news_push_bulk')
            ->label('Push teraz')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('info')
            ->schema([
                TextInput::make('title')
                    ->label('Tytul')
                    ->required()
                    ->maxLength(120)
                    ->default('Aktualizacja z wybranych parafii'),
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
                $newsPosts = collect($records)
                    ->filter(fn ($record): bool => $record instanceof NewsPost && $record->status === 'published')
                    ->values();

                $parishIds = $newsPosts->pluck('parish_id')->filter()->unique()->values();

                $users = $audiences->homeParishUsers($parishIds, withDevices: true);

                $result = $service->queuePushToUsers(
                    users: $users,
                    title: (string) $data['title'],
                    body: (string) $data['body'],
                    type: 'MANUAL_MESSAGE',
                    routingData: [
                        'scope' => 'news_posts_bulk',
                        'news_ids' => json_encode($newsPosts->map(fn (NewsPost $record): int => (int) $record->getKey())->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'parish_ids' => json_encode($parishIds->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'source' => 'superadmin_bulk',
                    ],
                    preferenceTopic: 'news',
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano push dla zaznaczonych aktualnosci.')
                    ->body("Uzytkownicy: {$result['users']} · urzadzenia: {$result['devices']} · skipped: {$result['skipped']}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function sendInstantEmailBulkAction(): BulkAction
    {
        return BulkAction::make('send_news_email_bulk')
            ->label('Email teraz')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->schema([
                TextInput::make('subject')
                    ->label('Temat')
                    ->required()
                    ->maxLength(200)
                    ->default('Aktualizacja z wybranych parafii'),
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
                    ->filter(fn ($record): bool => $record instanceof NewsPost && $record->status === 'published')
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
                    options: ['preference_topic' => 'news'],
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano email dla zaznaczonych aktualnosci.')
                    ->body("Odbiorcy: {$result['users']} · queued: {$result['queued']} · skipped: {$result['skipped']}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @return array<string, mixed>
     */
    protected static function resolveStatusPayload(string $status, NewsPost $record): array
    {
        $payload = [
            'status' => $status,
        ];

        if ($status === 'published') {
            $payload['published_at'] = $record->published_at ?? now();
            $payload['scheduled_for'] = null;
        }

        if ($status === 'scheduled') {
            $payload['published_at'] = null;
            $payload['scheduled_for'] = ($record->scheduled_for && $record->scheduled_for->isFuture())
                ? $record->scheduled_for
                : now()->addDay();
        }

        if ($status === 'draft') {
            $payload['published_at'] = null;
            $payload['scheduled_for'] = null;
        }

        if ($status === 'archived') {
            $payload['published_at'] = $record->published_at ?? now();
            $payload['scheduled_for'] = null;
        }

        return $payload;
    }

    protected static function statusColor(string $status): string
    {
        return match ($status) {
            'published' => 'success',
            'scheduled' => 'warning',
            'archived' => 'gray',
            default => 'info',
        };
    }
}
