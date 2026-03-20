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
        $parish = $this->activeParishOrFail($parishId);
        $mass = Mass::query()->where('parish_id', $parish->getKey())->findOrFail($massId);

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
        $parish = $this->activeParishOrFail($parishId);
        $mass = Mass::query()->where('parish_id', $parish->getKey())->findOrFail($massId);

        $mass->participants()->detach($request->user()->getKey());

        return $this->noContent();
    }

    public function addComment(StoreNewsCommentRequest $request, int $parishId, int $newsId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);

        $post = NewsPost::query()
            ->where('parish_id', $parish->getKey())
            ->published()
            ->with('parish')
            ->findOrFail($newsId);

        if (! $post->allowsComments()) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Komentowanie jest wylaczone dla tego wpisu.', 403);
        }

        if ($post->requiresVerifiedCommenter() && ! $request->user()?->is_user_verified) {
            throw new ApiException(ErrorCode::PARISH_APPROVAL_REQUIRED, 'Komentowanie tego wpisu wymaga zatwierdzenia parafialnego.', 403);
        }

        $parent = null;

        if (filled($request->input('parent_id'))) {
            $parent = NewsComment::query()
                ->where('news_post_id', $post->getKey())
                ->findOrFail((int) $request->integer('parent_id'));

            if ($parent->is_hidden) {
                throw new ApiException(ErrorCode::CONFLICT, 'Nie mozna odpowiadac na ukryty komentarz.', 409);
            }

            NewsComment::assertParentBelongsToPost((int) $post->getKey(), $parent);
        }

        $comment = NewsComment::query()->create([
            'news_post_id' => $post->getKey(),
            'user_id' => $request->user()->getKey(),
            'parent_id' => $parent?->getKey(),
            'depth' => NewsComment::resolveDepth($parent),
            'body' => (string) $request->string('body'),
        ]);

        return $this->success([
            'comment' => [
                'id' => (string) $comment->getKey(),
                'parent_id' => $comment->parent_id ? (string) $comment->parent_id : null,
                'depth' => (int) $comment->depth,
                'body' => $comment->body,
                'is_hidden' => false,
                'can_reply' => $comment->canReceiveReplies(),
                'user' => [
                    'id' => (string) $request->user()->getKey(),
                    'name' => $request->user()->full_name ?: $request->user()->name,
                    'avatar_url' => $request->user()->avatar_media_url,
                ],
                'created_at' => optional($comment->created_at)?->toISOString(),
                'updated_at' => optional($comment->updated_at)?->toISOString(),
                'replies_count' => 0,
                'replies' => [],
            ],
        ], 201);
    }

    public function deleteComment(Request $request, int $parishId, int $newsId, int $commentId): JsonResponse
    {
        $parish = $this->activeParishOrFail($parishId);

        $post = NewsPost::query()
            ->where('parish_id', $parish->getKey())
            ->published()
            ->findOrFail($newsId);

        $comment = NewsComment::query()
            ->where('news_post_id', $post->getKey())
            ->findOrFail($commentId);

        if ((int) $comment->user_id !== (int) $request->user()->getKey()) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Możesz usunąć tylko własny komentarz.', 403);
        }

        $comment->markHidden();

        return $this->noContent();
    }
}
