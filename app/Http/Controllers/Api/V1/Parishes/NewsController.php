<?php

namespace App\Http\Controllers\Api\V1\Parishes;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\NewsComment;
use App\Models\NewsPost;
use App\Support\Api\CursorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends ApiController
{
    public function index(Request $request, int $parishId): JsonResponse
    {
        $query = NewsPost::query()
            ->where('parish_id', $parishId)
            ->published();

        $paginated = CursorPaginator::paginate(
            query: $query->orderByDesc('is_pinned')->orderByDesc('published_at'),
            limit: 20,
            cursor: $request->query('cursor') ? (string) $request->query('cursor') : null,
            column: 'id',
            direction: 'desc',
        );

        return $this->collection(
            items: collect($paginated['items'])->map(fn (NewsPost $post) => $this->payload($post))->all(),
            nextCursor: $paginated['next_cursor'],
            hasMore: $paginated['has_more'],
        );
    }

    public function show(int $parishId, int $newsId): JsonResponse
    {
        $post = NewsPost::query()
            ->where('parish_id', $parishId)
            ->published()
            ->findOrFail($newsId);

        return $this->success([
            'news' => $this->payload($post, true),
        ]);
    }

    public function comments(Request $request, int $parishId, int $newsId): JsonResponse
    {
        $post = NewsPost::query()
            ->where('parish_id', $parishId)
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
                    'avatar_url' => $comment->user?->getFirstMediaUrl('avatar', 'thumb') ?: null,
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
            'comments_count' => $post->comments()->count(),
        ];

        if ($withContent) {
            $payload['content'] = $post->content;
        }

        return $payload;
    }
}
