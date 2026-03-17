<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ParishApprovalStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly bool $isApproved) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
}
