<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParishApprovalStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly bool $isApproved) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User
            && app(NotificationPreferenceResolver::class)->wantsEmail($notifiable, 'parish_approval_status')
            && filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        $parishId = $notifiable instanceof User
            ? ($notifiable->home_parish_id ?: $notifiable->current_parish_id)
            : null;

        return [
            'type' => 'PARISH_APPROVAL_STATUS_CHANGED',
            'title' => 'Zmiana statusu zatwierdzenia',
            'body' => $this->isApproved
                ? 'Twoje konto zostało zatwierdzone przez parafię.'
                : 'Status zatwierdzenia parafialnego Twojego konta został cofnięty.',
            'data' => [
                'is_parish_approved' => $this->isApproved,
                'parish_id' => $parishId ? (string) $parishId : null,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Zmiana statusu zatwierdzenia parafialnego')
            ->line($this->isApproved
                ? 'Twoje konto zostało zatwierdzone przez parafię.'
                : 'Status zatwierdzenia parafialnego Twojego konta został cofnięty.')
            ->line('Szczegóły znajdziesz w aplikacji.');
    }
}
