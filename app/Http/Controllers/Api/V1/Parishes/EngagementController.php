<?php

namespace App\Http\Controllers\Api\V1\Parishes;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\Me\StoreNewsCommentRequest;
use App\Models\Mass;
use App\Models\NewsComment;
use App\Models\NewsPost;
use App\Support\Api\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngagementController extends ApiController
{
    public function attendMass(Request $request, int $parishId, int $massId): JsonResponse
    {
        $mass = Mass::query()->where('parish_id', $parishId)->findOrFail($massId);

        $mass->participants()->syncWithoutDetaching([
            $request->user()->getKey() => ['registered_at' => now()],
        ]);

        return $this->success([
            'attending' => true,
            'mass_id' => (string) $mass->getKey(),
        ], 201);
    }

    public function cancelMassAttendance(Request $request, int $parishId, int $massId): JsonResponse
    {
        $mass = Mass::query()->where('parish_id', $parishId)->findOrFail($massId);

        $mass->participants()->detach($request->user()->getKey());

        return $this->noContent();
    }

    public function addComment(StoreNewsCommentRequest $request, int $parishId, int $newsId): JsonResponse
    {
        $post = NewsPost::query()
            ->where('parish_id', $parishId)
            ->published()
            ->findOrFail($newsId);

        $comment = NewsComment::query()->create([
            'news_post_id' => $post->getKey(),
            'user_id' => $request->user()->getKey(),
            'body' => (string) $request->string('body'),
        ]);

        return $this->success([
            'comment' => [
                'id' => (string) $comment->getKey(),
                'body' => $comment->body,
                'created_at' => optional($comment->created_at)?->toISOString(),
            ],
        ], 201);
    }

    public function deleteComment(Request $request, int $parishId, int $newsId, int $commentId): JsonResponse
    {
        $post = NewsPost::query()
            ->where('parish_id', $parishId)
            ->published()
            ->findOrFail($newsId);

        $comment = NewsComment::query()
            ->where('news_post_id', $post->getKey())
            ->findOrFail($commentId);

        if ((int) $comment->user_id !== (int) $request->user()->getKey()) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Możesz usunąć tylko własny komentarz.', 403);
        }

        $comment->delete();

        return $this->noContent();
    }
}
