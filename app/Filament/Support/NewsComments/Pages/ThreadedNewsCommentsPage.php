<?php

namespace App\Filament\Support\NewsComments\Pages;

use App\Models\NewsComment;
use App\Models\NewsPost;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class ThreadedNewsCommentsPage extends ListRecords
{
    protected string $view = 'filament.support.news-comments.pages.list-news-comments';

    protected Width|string|null $maxContentWidth = Width::Full;

    /**
     * @var array<int|string, string>
     */
    public array $replyBodies = [];

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<int, array{label:string, value:string, description:string, tone:string}>
     */
    public function getCommentMetrics(): array
    {
        $baseQuery = $this->getCommentsScopeQuery(includeTrashed: $this->canSeeTrashedComments())
            ->when(
                $this->selectedPostId(),
                fn (Builder $query, int $postId) => $query->where('news_post_id', $postId),
            );

        $total = (clone $baseQuery)->count();
        $visible = (clone $baseQuery)
            ->whereNull('deleted_at')
            ->where('is_hidden', false)
            ->count();
        $hidden = (clone $baseQuery)
            ->whereNull('deleted_at')
            ->where('is_hidden', true)
            ->count();
        $replies = (clone $baseQuery)->where('depth', '>', 0)->count();

        return [
            [
                'label' => 'Wszystkie komentarze',
                'value' => (string) $total,
                'description' => 'Pelny zakres roboczy dla aktualnych filtrow i wybranego wpisu.',
                'tone' => 'neutral',
            ],
            [
                'label' => 'Widoczne',
                'value' => (string) $visible,
                'description' => 'Komentarze, ktore nadal sa publicznie widoczne na froncie.',
                'tone' => 'calm',
            ],
            [
                'label' => 'Ukryte',
                'value' => (string) $hidden,
                'description' => 'Komentarze zdjete z widoku, ale zachowane w drzewie odpowiedzi.',
                'tone' => 'warm',
            ],
            [
                'label' => 'Odpowiedzi',
                'value' => (string) $replies,
                'description' => 'Wszystkie komentarze zagniezdzone pod watkami glownymi.',
                'tone' => 'cool',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getPostFilterOptions(): array
    {
        return NewsPost::query()
            ->when(
                $this->isAdminPanel(),
                fn (Builder $query) => $query->where('parish_id', Filament::getTenant()?->getKey()),
            )
            ->whereHas('comments')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(250)
            ->get()
            ->mapWithKeys(fn (NewsPost $post): array => [$post->getKey() => $post->getDisplayTitle()])
            ->all();
    }

    public function getCurrentVisibilityFilter(): string
    {
        $value = strtolower(trim((string) request()->query('visibility', 'all')));
        $allowed = $this->canSeeTrashedComments()
            ? ['all', 'visible', 'hidden', 'trashed']
            : ['all', 'visible', 'hidden'];

        return in_array($value, $allowed, true) ? $value : 'all';
    }

    public function getCurrentSearch(): string
    {
        return trim((string) request()->query('search', ''));
    }

    public function getCurrentPostFilter(): ?int
    {
        return $this->selectedPostId();
    }

    public function getResetFiltersUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    public function getCommentThreads(): LengthAwarePaginator
    {
        return $this->getRootCommentsQuery()
            ->paginate(18, pageName: 'threads')
            ->withQueryString();
    }

    public function canReplyToComment(NewsComment $comment): bool
    {
        return (! $comment->trashed())
            && (! $comment->is_hidden)
            && $comment->canReceiveReplies();
    }

    public function canHideComment(NewsComment $comment): bool
    {
        return (! $comment->trashed()) && (! $comment->is_hidden);
    }

    public function canRestoreVisibility(NewsComment $comment): bool
    {
        return $this->canModerateFully()
            && (! $comment->trashed())
            && (bool) $comment->is_hidden;
    }

    public function canRestoreDeleted(NewsComment $comment): bool
    {
        return $this->canModerateFully() && $comment->trashed();
    }

    public function canDeleteComment(NewsComment $comment): bool
    {
        return $this->canModerateFully() && (! $comment->trashed());
    }

    public function commentEditUrl(NewsComment $comment): string
    {
        return static::getResource()::getUrl('edit', ['record' => $comment]);
    }

    public function postEditUrl(?NewsComment $comment): ?string
    {
        if (! $comment?->newsPost) {
            return null;
        }

        return $this->getPostResourceClass()::getUrl('edit', ['record' => $comment->newsPost]);
    }

    public function commentAuthorName(NewsComment $comment): string
    {
        return $comment->user?->full_name
            ?: ($comment->user?->name ?? 'Brak autora');
    }

    public function commentStatusLabel(NewsComment $comment): string
    {
        if ($comment->trashed()) {
            return 'Usuniety';
        }

        if ($comment->is_hidden) {
            return 'Ukryty';
        }

        return 'Widoczny';
    }

    public function replyToComment(int $commentId): void
    {
        $comment = $this->findCommentForAction($commentId);

        if (! $this->canReplyToComment($comment)) {
            throw ValidationException::withMessages([
                "replyBodies.{$commentId}" => 'Na ten komentarz nie mozna juz odpowiedziec z poziomu listy.',
            ]);
        }

        $validator = Validator::make(
            ['body' => $this->replyBodies[$commentId] ?? ''],
            ['body' => ['required', 'string', 'min:1', 'max:2000']],
            ['body.required' => 'Tresci odpowiedzi nie mozna zostawic pustej.'],
        );

        $validator->validate();

        $author = Filament::auth()->user();

        NewsComment::query()->create([
            'news_post_id' => $comment->news_post_id,
            'user_id' => $author instanceof User ? $author->getKey() : null,
            'parent_id' => $comment->getKey(),
            'depth' => NewsComment::resolveDepth($comment),
            'body' => (string) $validator->validated()['body'],
        ]);

        unset($this->replyBodies[$commentId]);

        Notification::make()
            ->success()
            ->title('Odpowiedz zostala dodana.')
            ->send();

        $this->dispatch('news-comment-reply-saved', commentId: $commentId);
    }

    public function hideComment(int $commentId): void
    {
        $comment = $this->findCommentForAction($commentId);

        if (! $this->canHideComment($comment)) {
            return;
        }

        $actor = Filament::auth()->user();

        $comment->markHidden($actor instanceof User ? $actor : null);

        Notification::make()
            ->success()
            ->title('Komentarz zostal ukryty.')
            ->send();
    }

    public function restoreVisibility(int $commentId): void
    {
        if (! $this->canModerateFully()) {
            return;
        }

        $comment = $this->findCommentForAction($commentId, withTrashed: true);

        if (! $this->canRestoreVisibility($comment)) {
            return;
        }

        $comment->restoreVisibility();

        Notification::make()
            ->success()
            ->title('Komentarz znowu jest widoczny.')
            ->send();
    }

    public function restoreDeletedComment(int $commentId): void
    {
        if (! $this->canModerateFully()) {
            return;
        }

        $comment = $this->findCommentForAction($commentId, withTrashed: true);

        if (! $comment->trashed()) {
            return;
        }

        $comment->restore();

        Notification::make()
            ->success()
            ->title('Komentarz zostal przywrocony.')
            ->send();
    }

    public function deleteComment(int $commentId): void
    {
        if (! $this->canModerateFully()) {
            return;
        }

        $comment = $this->findCommentForAction($commentId, withTrashed: true);

        if ($comment->trashed()) {
            return;
        }

        $actor = Filament::auth()->user();

        if ($comment->children()->withTrashed()->exists()) {
            $comment->markHidden($actor instanceof User ? $actor : null);

            Notification::make()
                ->warning()
                ->title('Komentarz ma odpowiedzi, dlatego zostal ukryty zamiast usuniecia.')
                ->send();

            return;
        }

        $comment->delete();

        Notification::make()
            ->success()
            ->title('Komentarz zostal usuniety.')
            ->send();
    }

    protected function getRootCommentsQuery(): Builder
    {
        $query = $this->getCommentsScopeQuery(includeTrashed: $this->canSeeTrashedComments())
            ->whereNull('parent_id')
            ->with($this->threadRelationships())
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($postId = $this->selectedPostId()) {
            $query->where('news_post_id', $postId);
        }

        $this->applyVisibilityFilter($query, $this->getCurrentVisibilityFilter());
        $this->applySearchFilter($query, $this->getCurrentSearch());

        return $query;
    }

    protected function getCommentsScopeQuery(bool $includeTrashed = false): Builder
    {
        $query = NewsComment::query();

        if ($includeTrashed) {
            $query->withoutGlobalScopes([SoftDeletingScope::class])->withTrashed();
        }

        if ($this->isAdminPanel()) {
            $tenantId = Filament::getTenant()?->getKey();

            return $query->whereHas('newsPost', function (Builder $newsQuery) use ($tenantId): void {
                $newsQuery->where('parish_id', $tenantId ?? 0);
            });
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    protected function threadRelationships(): array
    {
        $includeTrashed = $this->canSeeTrashedComments();

        return [
            'newsPost',
            'user',
            'hiddenBy',
            'children' => function ($query) use ($includeTrashed): void {
                if ($includeTrashed) {
                    $query->withoutGlobalScopes([SoftDeletingScope::class])->withTrashed();
                }

                $query
                    ->with(['user', 'hiddenBy'])
                    ->with([
                        'children' => function ($nestedQuery) use ($includeTrashed): void {
                            if ($includeTrashed) {
                                $nestedQuery->withoutGlobalScopes([SoftDeletingScope::class])->withTrashed();
                            }

                            $nestedQuery
                                ->with(['user', 'hiddenBy'])
                                ->orderBy('created_at')
                                ->orderBy('id');
                        },
                    ])
                    ->orderBy('created_at')
                    ->orderBy('id');
            },
        ];
    }

    protected function applyVisibilityFilter(Builder $query, string $visibility): void
    {
        match ($visibility) {
            'visible' => $query
                ->whereNull('deleted_at')
                ->where('is_hidden', false),
            'hidden' => $query
                ->whereNull('deleted_at')
                ->where('is_hidden', true),
            'trashed' => $this->canSeeTrashedComments()
                ? $query->onlyTrashed()
                : null,
            default => null,
        };
    }

    protected function applySearchFilter(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function (Builder $searchQuery) use ($like): void {
            $this->applySelfSearch($searchQuery, $like);

            $searchQuery->orWhereHas('children', function (Builder $childQuery) use ($like): void {
                $this->applySelfSearch($childQuery, $like);
            });

            $searchQuery->orWhereHas('children.children', function (Builder $grandchildQuery) use ($like): void {
                $this->applySelfSearch($grandchildQuery, $like);
            });
        });
    }

    protected function applySelfSearch(Builder $query, string $like): void
    {
        $query
            ->where('body', 'like', $like)
            ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                $userQuery
                    ->where('full_name', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orWhereHas('newsPost', function (Builder $postQuery) use ($like): void {
                $postQuery
                    ->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
    }

    protected function findCommentForAction(int $commentId, bool $withTrashed = false): NewsComment
    {
        $query = $this->getCommentsScopeQuery(includeTrashed: $withTrashed)->with(['newsPost', 'user', 'children']);

        return $query->findOrFail($commentId);
    }

    protected function selectedPostId(): ?int
    {
        $value = request()->query('post');

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public function canSeeTrashedComments(): bool
    {
        return $this->canModerateFully();
    }

    protected function isAdminPanel(): bool
    {
        return ! $this->canModerateFully();
    }

    abstract protected function canModerateFully(): bool;

    /**
     * @return class-string
     */
    abstract protected function getPostResourceClass(): string;
}
