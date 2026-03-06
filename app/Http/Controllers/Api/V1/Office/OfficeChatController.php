<?php

namespace App\Http\Controllers\Api\V1\Office;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\Office\CreateChatRequest;
use App\Http\Requests\Api\Office\StoreMessageRequest;
use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\User;
use App\Support\Api\CursorPaginator;
use App\Support\Api\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class OfficeChatController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = OfficeConversation::query()
            ->where('parishioner_user_id', $user->getKey())
            ->with(['parish', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get();

        return $this->success([
            'items' => $conversations->map(fn (OfficeConversation $chat) => [
                'id' => (string) $chat->getKey(),
                'uuid' => $chat->uuid,
                'parish_id' => (string) $chat->parish_id,
                'parish_name' => $chat->parish?->short_name ?: $chat->parish?->name,
                'status' => $chat->status,
                'last_message_at' => optional($chat->last_message_at)?->toISOString(),
                'last_message_preview' => $chat->latestMessage?->body,
                'created_at' => optional($chat->created_at)?->toISOString(),
                'updated_at' => optional($chat->updated_at)?->toISOString(),
            ])->values(),
        ]);
    }

    public function store(CreateChatRequest $request): JsonResponse
    {
        $user = $request->user();
        $parish = $this->activeParishOrFail((int) $request->input('parish_id'));

        if ((int) $user->home_parish_id !== (int) $parish->getKey()) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Możesz rozpocząć czat wyłącznie z własną parafią.', 403);
        }

        $priest = $this->resolvePriestForParish($parish->getKey());

        if (! $priest) {
            throw new ApiException(ErrorCode::NOT_FOUND, 'Brak dostępnego opiekuna kancelarii dla parafii.', 404);
        }

        $conversation = DB::transaction(function () use ($request, $user, $parish, $priest): OfficeConversation {
            $conversation = OfficeConversation::query()->create([
                'parish_id' => $parish->getKey(),
                'parishioner_user_id' => $user->getKey(),
                'priest_user_id' => $priest->getKey(),
                'status' => OfficeConversation::STATUS_OPEN,
                'last_message_at' => now(),
            ]);

            OfficeMessage::query()->create([
                'office_conversation_id' => $conversation->getKey(),
                'sender_user_id' => $user->getKey(),
                'body' => (string) $request->string('message'),
                'has_attachments' => false,
            ]);

            return $conversation->fresh();
        });

        return $this->success([
            'chat' => [
                'id' => (string) $conversation->getKey(),
                'uuid' => $conversation->uuid,
                'parish_id' => (string) $conversation->parish_id,
                'status' => $conversation->status,
                'last_message_at' => optional($conversation->last_message_at)?->toISOString(),
                'created_at' => optional($conversation->created_at)?->toISOString(),
            ],
        ], 201);
    }

    public function messages(Request $request, int $chatId): JsonResponse
    {
        $conversation = $this->conversationForUser($request->user()->getKey(), $chatId);

        $query = OfficeMessage::query()
            ->where('office_conversation_id', $conversation->getKey())
            ->with('sender')
            ->orderByDesc('id');

        $paginated = CursorPaginator::paginate(
            query: $query,
            limit: 30,
            cursor: $request->query('cursor') ? (string) $request->query('cursor') : null,
            column: 'id',
            direction: 'desc',
        );

        OfficeMessage::query()
            ->where('office_conversation_id', $conversation->getKey())
            ->where('sender_user_id', '!=', $request->user()->getKey())
            ->whereNull('read_by_parishioner_at')
            ->update(['read_by_parishioner_at' => now()]);

        return $this->collection(
            items: collect($paginated['items'])->map(fn (OfficeMessage $message) => $this->messagePayload($message))->all(),
            nextCursor: $paginated['next_cursor'],
            hasMore: $paginated['has_more'],
        );
    }

    public function storeMessage(StoreMessageRequest $request, int $chatId): JsonResponse
    {
        $conversation = $this->conversationForUser($request->user()->getKey(), $chatId);

        $message = DB::transaction(function () use ($request, $conversation): OfficeMessage {
            $message = OfficeMessage::query()->create([
                'office_conversation_id' => $conversation->getKey(),
                'sender_user_id' => $request->user()->getKey(),
                'body' => (string) $request->string('body'),
                'has_attachments' => false,
            ]);

            $conversation->forceFill(['last_message_at' => now()])->save();

            return $message->load('sender');
        });

        return $this->success([
            'message' => $this->messagePayload($message),
        ], 201);
    }

    public function storeAttachments(StoreMessageRequest $request, int $chatId): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:5'],
            'files.*' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx'],
        ]);

        $conversation = $this->conversationForUser($request->user()->getKey(), $chatId);

        $message = DB::transaction(function () use ($request, $conversation): OfficeMessage {
            $message = OfficeMessage::query()->create([
                'office_conversation_id' => $conversation->getKey(),
                'sender_user_id' => $request->user()->getKey(),
                'body' => $request->filled('body') ? (string) $request->string('body') : null,
                'has_attachments' => true,
            ]);

            /** @var UploadedFile $file */
            foreach ($request->file('files', []) as $file) {
                $message->addMedia($file)->toMediaCollection('attachments');
            }

            $conversation->forceFill(['last_message_at' => now()])->save();

            return $message->load('sender');
        });

        return $this->success([
            'message' => $this->messagePayload($message, true),
        ], 201);
    }

    private function resolvePriestForParish(int $parishId): ?User
    {
        return User::query()
            ->where('role', '>=', 1)
            ->where('status', 'active')
            ->whereHas('managedParishes', function ($query) use ($parishId): void {
                $query->where('parish_id', $parishId)
                    ->where('parish_user.is_active', true);
            })
            ->orderByDesc('role')
            ->first();
    }

    private function conversationForUser(int $userId, int $chatId): OfficeConversation
    {
        $conversation = OfficeConversation::query()
            ->where('parishioner_user_id', $userId)
            ->find($chatId);

        if (! $conversation) {
            throw new ApiException(ErrorCode::NOT_FOUND, 'Nie znaleziono konwersacji.', 404);
        }

        return $conversation;
    }

    private function messagePayload(OfficeMessage $message, bool $withAttachments = true): array
    {
        $payload = [
            'id' => (string) $message->getKey(),
            'chat_id' => (string) $message->office_conversation_id,
            'sender_user_id' => (string) $message->sender_user_id,
            'body' => $message->body,
            'has_attachments' => (bool) $message->has_attachments,
            'created_at' => optional($message->created_at)?->toISOString(),
            'updated_at' => optional($message->updated_at)?->toISOString(),
        ];

        if ($withAttachments && $message->has_attachments) {
            $payload['attachments'] = $message->getMedia('attachments')->map(fn ($media) => [
                'id' => (string) $media->getKey(),
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'download_url' => route('api.v1.office.attachments.show', [
                    'chatId' => $message->office_conversation_id,
                    'attachmentId' => $media->getKey(),
                ]),
            ])->values();
        }

        return $payload;
    }
}
