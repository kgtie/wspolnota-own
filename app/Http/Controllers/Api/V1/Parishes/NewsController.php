<?php

namespace App\Http\Controllers\Api\V1\Parishes;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\NewsComment;
use App\Models\NewsPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NewsController extends ApiController
{
    public function index(Request $request, int $parishId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);
        [$cursorSortAt, $cursorId] = $this->decodeCursor($request->query('cursor'));

        $query = NewsPost::query()
            ->where('parish_id', $parish->getKey())
            ->published()
            ->withCount('comments')
            ->select('news_posts.*')
            ->selectRaw('COALESCE(news_posts.published_at, news_posts.created_at) as api_sort_at');

        if ($cursorSortAt && $cursorId) {
            $query->where(function ($inner) use ($cursorSortAt, $cursorId): void {
                $inner->whereRaw('COALESCE(news_posts.published_at, news_posts.created_at) < ?', [$cursorSortAt])
                    ->orWhere(function ($sameSort) use ($cursorSortAt, $cursorId): void {
                        $sameSort
                            ->whereRaw('COALESCE(news_posts.published_at, news_posts.created_at) = ?', [$cursorSortAt])
                            ->where('news_posts.id', '<', $cursorId);
                    });
            });
        }

        $limit = 20;
        $rows = $query
            ->orderByRaw('COALESCE(news_posts.published_at, news_posts.created_at) desc')
            ->orderByDesc('news_posts.id')
            ->limit($limit + 1)
            ->get();

        [$items, $nextCursor, $hasMore] = $this->finalizePage($rows, $limit);

        return $this->collection(
            items: $items->map(fn (NewsPost $post) => $this->payload($post))->all(),
            nextCursor: $nextCursor,
            hasMore: $hasMore,
        );
    }

    public function show(int $parishId, int $newsId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);

        $post = NewsPost::query()
            ->where('parish_id', $parish->getKey())
            ->published()
            ->withCount('comments')
            ->findOrFail($newsId);

        return $this->success([
            'news' => $this->payload($post, true),
        ]);
    }

    public function comments(Request $request, int $parishId, int $newsId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);

        $post = NewsPost::query()
            ->where('parish_id', $parish->getKey())
            ->published()
            ->findOrFail($newsId);

        $query = NewsComment::query()
            ->where('news_post_id', $post->getKey())
            ->with('user')
            ->orderByDesc('id');

        $paginated = CursorPaginator::paginate(
            query: $query,
            limit: 20,
            cursor: $request->query('cursor') ? (string) $request->query('cursor') : null,
            column: 'id',
            direction: 'desc',
        );

        return $this->collection(
            items: collect($paginated['items'])->map(fn (NewsComment $comment) => [
                'id' => (string) $comment->getKey(),
                'body' => $comment->body,
                'user' => [
                    'id' => (string) $comment->user?->getKey(),
                    'name' => $comment->user?->full_name ?: $comment->user?->name,
                    'avatar_url' => $comment->user?->avatar_media_url,
                ],
                'created_at' => optional($comment->created_at)?->toISOString(),
                'updated_at' => optional($comment->updated_at)?->toISOString(),
            ])->all(),
            nextCursor: $paginated['next_cursor'],
            hasMore: $paginated['has_more'],
        );
    }

    private function payload(NewsPost $post, bool $withContent = false): array
    {
        $payload = [
            'id' => (string) $post->getKey(),
            'parish_id' => (string) $post->parish_id,
            'title' => $post->title,
            'slug' => $post->slug,
            'is_pinned' => (bool) $post->is_pinned,
            'featured_image_url' => $post->getFirstMediaUrl('featured_image', 'preview') ?: null,
            'published_at' => optional($post->published_at)?->toISOString(),
            'created_at' => optional($post->created_at)?->toISOString(),
            'updated_at' => optional($post->updated_at)?->toISOString(),
            'comments_count' => (int) ($post->comments_count ?? $post->comments()->count()),
        ];

        if ($withContent) {
            $payload['content'] = $post->content;
        }

        return $payload;
    }

    private function finalizePage(Collection $rows, int $limit): array
    {
        $hasMore = $rows->count() > $limit;
        $items = $rows->take($limit)->values();
        $nextCursor = null;

        if ($hasMore && $items->isNotEmpty()) {
            /** @var NewsPost $last */
            $last = $items->last();
            $sortAt = $last->getAttribute('api_sort_at') ?? $last->published_at ?? $last->created_at;
            $nextCursor = $this->encodeCursor((string) $sortAt, (int) $last->getKey());
        }

        return [$items, $nextCursor, $hasMore];
    }

    private function encodeCursor(string $sortAt, int $id): string
    {
        $json = json_encode(['sort_at' => $sortAt, 'id' => $id], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    private function decodeCursor(mixed $cursor): array
    {
        if (! is_string($cursor) || $cursor === '') {
            return [null, null];
        }

        $padding = strlen($cursor) % 4;
        if ($padding > 0) {
            $cursor .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);

        if (! is_string($decoded) || $decoded === '') {
            return [null, null];
        }

        $payload = json_decode($decoded, true);

        if (! is_array($payload) || ! isset($payload['sort_at'], $payload['id'])) {
            return [null, null];
        }

        return [(string) $payload['sort_at'], (int) $payload['id']];
    }
}
