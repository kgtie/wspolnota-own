<?php

namespace App\Http\Controllers\Api\V1\Office;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OfficeAttachmentController extends ApiController
{
    public function show(Request $request, int $chatId, int $attachmentId): BinaryFileResponse
    {
        $conversation = OfficeConversation::query()
            ->whereKey($chatId)
            ->where('parishioner_user_id', $request->user()->getKey())
            ->firstOrFail();

        $media = Media::query()->findOrFail($attachmentId);

        if ($media->model_type !== OfficeMessage::class || $media->collection_name !== 'attachments') {
            abort(404);
        }

        $message = OfficeMessage::query()
            ->whereKey($media->model_id)
            ->where('office_conversation_id', $conversation->getKey())
            ->firstOrFail();

        $path = $media->getPath();

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            abort(404);
        }

        activity('office-conversations')
            ->causedBy($request->user())
            ->performedOn($conversation)
            ->event('office_attachment_downloaded_via_api')
            ->withProperties([
                'parish_id' => $conversation->parish_id,
                'office_conversation_id' => $conversation->getKey(),
                'office_message_id' => $message->getKey(),
                'media_id' => $media->getKey(),
                'media_file_name' => $media->file_name,
            ])
            ->log('Pobrano zalacznik z konwersacji kancelarii online przez API.');

        return response()->download(
            $path,
            $media->file_name,
            [
                'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            ],
        );
    }
}
