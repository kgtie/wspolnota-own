<?php

namespace App\Console\Commands;

use App\Models\Mass;
use App\Models\User;
use App\Support\Notifications\MassReminderDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

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
            ->whereHas('participants', function ($query): void {
                $query
                    ->where('users.status', 'active')
                    ->whereNull('mass_user.reminder_email_sent_at');
            })
            ->orderBy('celebration_at')
            ->limit($limit)
            ->get();

        $sent = 0;
        $pendingByUser = collect();

        foreach ($masses as $mass) {
            foreach ($mass->participants as $participant) {
                if (! $participant instanceof User || $participant->status !== 'active') {
                    continue;
                }

                if ($participant->pivot?->reminder_email_sent_at) {
                    continue;
                }

                /** @var Collection<int,Mass> $bucket */
                $bucket = $pendingByUser->get($participant->getKey(), collect());
                $bucket->push($mass);
                $pendingByUser->put($participant->getKey(), $bucket);
            }
        }

        foreach ($pendingByUser as $userId => $userMasses) {
            $user = $userMasses->first()?->participants
                ?->firstWhere('id', (int) $userId);

            if (! $user instanceof User) {
                continue;
            }

            if (! $dispatcher->dispatchMorningEmailDigest($user, $userMasses)) {
                continue;
            }

            foreach ($userMasses as $mass) {
                $mass->participants()->updateExistingPivot($user->getKey(), [
                    'reminder_email_sent_at' => now(),
                ]);
            }

            $sent++;
        }

        $this->info("Wyslano poranne digests przypominajace: {$sent}");

        return self::SUCCESS;
    }
}
