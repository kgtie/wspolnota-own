<?php

namespace App\Console\Commands;

use App\Models\Mass;
use App\Models\User;
use App\Support\Notifications\MassReminderDispatcher;
use Illuminate\Console\Command;

class DispatchMassPendingMorningEmailRemindersCommand extends Command
{
    protected $signature = 'masses:dispatch-morning-email-reminders {--limit=500 : Maksymalna liczba mszy do obslugi}';

    protected $description = 'Wysyla jeden poranny email o 5:00 dla dzisiejszych mszy zapisanych przez uzytkownika.';

    public function handle(MassReminderDispatcher $dispatcher): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $today = now()->toDateString();

        $masses = Mass::query()
            ->with(['participants.notificationPreference'])
            ->where('status', 'scheduled')
            ->whereDate('celebration_at', $today)
            ->orderBy('celebration_at')
            ->limit($limit)
            ->get();

        $sent = 0;

        foreach ($masses as $mass) {
            foreach ($mass->participants as $participant) {
                if (! $participant instanceof User || $participant->status !== 'active') {
                    continue;
                }

                if ($participant->pivot?->reminder_email_sent_at) {
                    continue;
                }

                if ($dispatcher->dispatchMorningEmailReminder($mass, $participant)) {
                    $mass->participants()->updateExistingPivot($participant->getKey(), [
                        'reminder_email_sent_at' => now(),
                    ]);
                    $sent++;
                }
            }
        }

        $this->info("Wyslano poranne emaile przypominajace: {$sent}");

        return self::SUCCESS;
    }
}
