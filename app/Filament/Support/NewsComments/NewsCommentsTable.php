<?php

namespace App\Filament\Support\NewsComments;

use App\Models\NewsComment;
use App\Models\NewsPost;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class NewsCommentsTable
{
    /**
     * @param  class-string  $postResourceClass
     * @param  class-string  $commentResourceClass
     */
    public static function configure(Table $table, bool $isSuperAdmin, string $postResourceClass, string $commentResourceClass): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->recordUrl(fn (NewsComment $record): string => $commentResourceClass::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('newsPost.title')
                    ->label('Wpis')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->join('news_posts', 'news_comments.news_post_id', '=', 'news_posts.id')
                        ->orderBy('news_posts.title', $direction)
                        ->select('news_comments.*'))
                    ->wrap()
                    ->formatStateUsing(fn (?string $state, NewsComment $record): string => $record->newsPost?->getDisplayTitle() ?? '-')
                    ->url(fn (NewsComment $record): ?string => $record->newsPost
                        ? $postResourceClass::getUrl('edit', ['record' => $record->newsPost])
                        : null),

                TextColumn::make('body_preview')
                    ->label('Komentarz')
                    ->state(fn (NewsComment $record): string => $record->is_hidden
                        ? '[Komentarz ukryty]'
                        : str($record->body)->squish()->limit(140)->toString())
                    ->description(fn (NewsComment $record): string => match ((int) $record->depth) {
                        1 => 'Odpowiedź na komentarz główny',
                        2 => 'Odpowiedz drugiego poziomu',
                        default => 'Komentarz główny',
                    })
                    ->wrap(),

                TextColumn::make('user.full_name')
                    ->label('Autor')
                    ->searchable()
                    ->state(fn (NewsComment $record): string => $record->user?->full_name ?: ($record->user?->name ?? 'Brak')),

                TextColumn::make('is_hidden')
                    ->label('Widoczność')
                    ->badge()
                    ->state(fn (NewsComment $record): string => $record->is_hidden ? 'Ukryty' : 'Widoczny')
                    ->color(fn (string $state): string => $state === 'Ukryty' ? 'warning' : 'success'),

                TextColumn::make('created_at')
                    ->label('Dodano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('news_post_id')
                    ->label('Wpis')
                    ->searchable()
                    ->options(fn (): array => NewsPost::query()
                        ->when(
                            ! $isSuperAdmin && filled(Filament::getTenant()?->getKey()),
                            fn (Builder $query) => $query->where('parish_id', Filament::getTenant()?->getKey()),
                        )
                        ->orderByDesc('created_at')
                        ->limit(200)
                        ->get()
                        ->mapWithKeys(fn (NewsPost $post): array => [$post->getKey() => $post->getDisplayTitle()])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('news_post_id', $data['value'])
                        : $query),

                SelectFilter::make('depth')
                    ->label('Poziom')
                    ->options([
                        '0' => 'Komentarze główne',
                        '1' => 'Odpowiedzi poziomu 1',
                        '2' => 'Odpowiedzi poziomu 2',
                    ]),

                TernaryFilter::make('is_hidden')
                    ->label('Ukrycie')
                    ->nullable()
                    ->trueLabel('Tylko ukryte')
                    ->falseLabel('Tylko widoczne'),

                TrashedFilter::make()
                    ->visible($isSuperAdmin),
            ])
            ->recordActions([
                ActionGroup::make(array_values(array_filter([
                    EditAction::make(),
                    self::replyAction(),
                    self::hideAction(),
                    $isSuperAdmin ? self::restoreVisibilityAction() : null,
                    $isSuperAdmin ? self::deleteCommentAction() : null,
                    $isSuperAdmin ? ForceDeleteAction::make() : null,
                    $isSuperAdmin ? RestoreAction::make() : null,
                ])))
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ]);
    }

    protected static function replyAction(): Action
    {
        return Action::make('reply')
                    ->label('Odpowiedz')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('info')
            ->visible(fn (NewsComment $record): bool => ! $record->is_hidden && $record->canReceiveReplies())
            ->schema([
                Textarea::make('body')
                    ->label('Treść odpowiedzi')
                    ->required()
                    ->rows(6)
                    ->maxLength(2000),
            ])
            ->action(function (NewsComment $record, array $data): void {
                if ($record->is_hidden) {
                    throw ValidationException::withMessages([
                        'body' => 'Nie można odpowiadać na ukryty komentarz.',
                    ]);
                }

                if (! $record->canReceiveReplies()) {
                    throw ValidationException::withMessages([
                        'body' => 'Ten komentarz osiągnął już maksymalną głębokość wątku.',
                    ]);
                }

                $author = Filament::auth()->user();

                NewsComment::query()->create([
                    'news_post_id' => $record->news_post_id,
                    'user_id' => $author instanceof User ? $author->getKey() : null,
                    'parent_id' => $record->getKey(),
                    'depth' => NewsComment::resolveDepth($record),
                    'body' => (string) ($data['body'] ?? ''),
                ]);

                Notification::make()
                    ->success()
                    ->title('Dodano odpowiedź na komentarz.')
                    ->send();
            });
    }

    protected static function hideAction(): Action
    {
        return Action::make('hide_comment')
            ->label('Ukryj')
            ->icon('heroicon-o-eye-slash')
            ->color('warning')
            ->visible(fn (NewsComment $record): bool => ! $record->is_hidden)
            ->requiresConfirmation()
            ->action(function (NewsComment $record): void {
                $actor = Filament::auth()->user();

                $record->markHidden($actor instanceof User ? $actor : null);

                Notification::make()
                    ->success()
                    ->title('Komentarz został ukryty.')
                    ->send();
            });
    }

    protected static function restoreVisibilityAction(): Action
    {
        return Action::make('restore_visibility')
            ->label('Przywróć widoczność')
            ->icon('heroicon-o-eye')
            ->color('success')
            ->visible(fn (NewsComment $record): bool => $record->is_hidden)
            ->action(function (NewsComment $record): void {
                $record->restoreVisibility();

                Notification::make()
                    ->success()
                    ->title('Komentarz znów jest widoczny.')
                    ->send();
            });
    }

    protected static function deleteCommentAction(): Action
    {
        return Action::make('delete_comment')
            ->label('Usuń')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (NewsComment $record): bool => ! $record->trashed())
            ->action(function (NewsComment $record): void {
                $actor = Filament::auth()->user();

                if ($record->children()->exists()) {
                    $record->markHidden($actor instanceof User ? $actor : null);

                    Notification::make()
                        ->warning()
                        ->title('Komentarz ma odpowiedzi, dlatego został ukryty zamiast usunięcia.')
                        ->send();

                    return;
                }

                $record->delete();

                Notification::make()
                    ->success()
                    ->title('Komentarz został usunięty.')
                    ->send();
            });
    }
}
