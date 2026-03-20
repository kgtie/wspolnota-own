<?php

namespace App\Filament\Admin\Resources\NewsPosts\Tables;

use App\Filament\Admin\Resources\NewsComments\NewsCommentResource;
use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use App\Models\NewsPost;
use App\Models\User;
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
use Filament\Facades\Filament;
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
            ->recordUrl(fn (NewsPost $record): string => NewsPostResource::getUrl('edit', ['record' => $record]))
            ->columns([
                SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label('Okladka')
                    ->collection('featured_image')
                    ->conversion('thumb')
                    ->height(64)
                    ->width(104),

                TextColumn::make('title')
                    ->label('Tytul')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(fn (?string $state, NewsPost $record): string => $record->getDisplayTitle())
                    ->description(fn (NewsPost $record): ?string => filled($record->content)
                        ? (string) str(strip_tags((string) $record->content))->squish()->limit(90)
                        : null),

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

                TernaryFilter::make('is_pinned')
                    ->label('Przypiety')
                    ->nullable()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('comments')
                        ->label('Komentarze')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->url(fn (NewsPost $record): string => NewsCommentResource::getUrl('index', [
                            'filters' => [
                                'news_post_id' => ['value' => $record->getKey()],
                            ],
                        ])),
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
