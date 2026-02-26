<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\OfficeMessage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OfficeAttachmentDownloadController extends Controller
{
    public function __invoke(Request $request, Media $media): BinaryFileResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($media->model_type !== OfficeMessage::class || $media->collection_name !== 'attachments') {
            abort(404);
        }

        $message = OfficeMessage::query()
            ->with('conversation')
            ->find($media->model_id);

        if (! $message || ! $message->conversation) {
            abort(404);
        }

        $conversation = $message->conversation;

        $isParticipant = (int) $conversation->priest_user_id === (int) $user->getKey()
            || (int) $conversation->parishioner_user_id === (int) $user->getKey();

        if (! $isParticipant) {
            abort(403);
        }

        $path = $media->getPath();

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            abort(404);
        }

        activity('office-conversations')
            ->causedBy($user)
            ->performedOn($conversation)
            ->event('office_attachment_downloaded')
            ->withProperties([
                'parish_id' => $conversation->parish_id,
                'office_conversation_id' => $conversation->getKey(),
                'office_message_id' => $message->getKey(),
                'media_id' => $media->getKey(),
                'media_file_name' => $media->file_name,
            ])
            ->log('Pobrano zalacznik z konwersacji kancelarii online.');

        return response()->download(
            $path,
            $media->file_name,
            [
                'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            ],
        );
    }
}
