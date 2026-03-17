<?php

namespace App\Console\Commands;

use App\Models\Mass;
use App\Models\User;
use App\Support\Notifications\MassReminderDispatcher;
use Illuminate\Console\Command;

class DispatchMassPendingPushRemindersCommand extends Command
{
    protected $signature = 'masses:dispatch-pending-reminders {--limit=250 : Maksymalna liczba mszy do obslugi}';

    protected $description = 'Wysyla przypomnienia push o zblizajacej sie mszy: 24h, 8h i 1h przed celebracja.';

    public function handle(MassReminderDispatcher $dispatcher): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $masses = Mass::query()
            ->with(['participants.notificationPreference'])
            ->where('status', 'scheduled')
            ->whereBetween('celebration_at', [now(), now()->addDay()])
            ->orderBy('celebration_at')
            ->limit($limit)
            ->get();

        $sent24h = 0;
        $sent8h = 0;
        $sent1h = 0;

        foreach ($masses as $mass) {
            foreach ($mass->participants as $participant) {
                if (! $participant instanceof User || $participant->status !== 'active') {
                    continue;
                }

                $hoursUntilMass = now()->diffInRealHours($mass->celebration_at, false);

                if ($hoursUntilMass <= 24 && $hoursUntilMass > 8 && ! $participant->pivot?->reminder_push_24h_sent_at) {
                    if ($dispatcher->dispatchPushReminder($mass, $participant, '24h')) {
                        $mass->participants()->updateExistingPivot($participant->getKey(), [
                            'reminder_push_24h_sent_at' => now(),
                        ]);
                        $sent24h++;
                    }
                }

                if ($hoursUntilMass <= 8 && $hoursUntilMass > 1 && ! $participant->pivot?->reminder_push_8h_sent_at) {
                    if ($dispatcher->dispatchPushReminder($mass, $participant, '8h')) {
                        $mass->participants()->updateExistingPivot($participant->getKey(), [
                            'reminder_push_8h_sent_at' => now(),
                        ]);
                        $sent8h++;
                    }
                }

                if ($hoursUntilMass <= 1 && $hoursUntilMass > 0 && ! $participant->pivot?->reminder_push_1h_sent_at) {
                    if ($dispatcher->dispatchPushReminder($mass, $participant, '1h')) {
                        $mass->participants()->updateExistingPivot($participant->getKey(), [
                            'reminder_push_1h_sent_at' => now(),
                        ]);
                        $sent1h++;
                    }
                }
            }
        }

        $this->table(
            ['Metryka', 'Wartosc'],
            [
                ['Przypomnienia 24h', (string) $sent24h],
                ['Przypomnienia 8h', (string) $sent8h],
                ['Przypomnienia 1h', (string) $sent1h],
            ],
        );

        return self::SUCCESS;
    }
}
