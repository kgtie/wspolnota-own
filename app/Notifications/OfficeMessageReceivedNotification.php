<?php

namespace App\Notifications;

use App\Models\OfficeMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OfficeMessageReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly OfficeMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $parishId = $this->message->conversation?->parish_id;

        return [
            'type' => 'OFFICE_MESSAGE_RECEIVED',
            'title' => 'Nowa wiadomość w kancelarii',
            'body' => 'Otrzymano nową wiadomość w wątku kancelarii online.',
            'data' => [
                'chat_id' => (string) $this->message->office_conversation_id,
                'message_id' => (string) $this->message->getKey(),
                'parish_id' => $parishId ? (string) $parishId : null,
            ],
        ];
    }
}
