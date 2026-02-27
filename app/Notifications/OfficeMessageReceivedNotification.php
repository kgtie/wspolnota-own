<?php

namespace App\Notifications;

use App\Models\OfficeMessage;
use App\Models\User;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfficeMessageReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly OfficeMessage $message) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User
            && app(NotificationPreferenceResolver::class)->wantsEmail($notifiable, 'office_messages')
            && filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'OFFICE_MESSAGE_RECEIVED',
            'title' => 'Nowa wiadomość w kancelarii',
            'body' => 'Otrzymano nową wiadomość w wątku kancelarii online.',
            'data' => [
                'chat_id' => (string) $this->message->office_conversation_id,
                'message_id' => (string) $this->message->getKey(),
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nowa wiadomość w kancelarii online')
            ->line('Otrzymałeś nową wiadomość w kancelarii online.')
            ->line('Otwórz aplikację, aby odpowiedzieć.');
    }
}
