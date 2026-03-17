<?php

namespace App\Notifications;

use App\Models\Mass;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MassPendingReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Mass $mass,
        public readonly string $reminderKey,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'MASS_PENDING',
            'title' => $this->title(),
            'body' => $this->body(),
            'data' => [
                'mass_id' => (string) $this->mass->getKey(),
                'parish_id' => (string) $this->mass->parish_id,
                'reminder_key' => $this->reminderKey,
                'celebration_at' => optional($this->mass->celebration_at)?->toISOString(),
            ],
        ];
    }

    private function title(): string
    {
        return match ($this->reminderKey) {
            '24h' => 'Msza juz jutro',
            '8h' => 'Msza dzisiaj',
            '1h' => 'Msza za chwile',
            default => 'Przypomnienie o mszy',
        };
    }

    private function body(): string
    {
        $intention = trim((string) $this->mass->intention_title);
        $suffix = $intention !== '' ? ': '.$intention : '.';

        return match ($this->reminderKey) {
            '24h' => 'Za 24 godziny rozpocznie sie msza'.$suffix,
            '8h' => 'Za 8 godzin rozpocznie sie msza'.$suffix,
            '1h' => 'Za 1 godzine rozpocznie sie msza'.$suffix,
            default => 'Zbliza sie msza'.$suffix,
        };
    }
}
