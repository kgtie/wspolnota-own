<?php

namespace App\Support\Reports;

use App\Mail\ParishPriestWeeklyDigestMessage;
use App\Models\Parish;
use App\Models\User;
use App\Support\Notifications\NotificationPreferenceResolver;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Mail;

class ParishPriestWeeklyDigestSender
{
    public function __construct(
        private readonly ParishPriestWeeklyDigestBuilder $builder,
        private readonly NotificationPreferenceResolver $preferences,
    ) {}

    /**
     * @return array{recipients:int,copies:int,skipped:int}
     */
    public function sendForParish(
        Parish $parish,
        CarbonInterface $generatedAt,
        bool $copyToSuperadmin = false,
        ?string $copyRecipient = null,
        ?User $actor = null,
    ): array {
        $recipients = $parish->admins()
            ->with('notificationPreference')
            ->where('users.status', 'active')
            ->where('users.role', '>=', 1)
            ->whereNotNull('users.email')
            ->get()
            ->filter(fn (User $user): bool => $this->preferences->wantsEmail($user, 'manual_messages'))
            ->unique(fn (User $user): string => mb_strtolower((string) $user->email))
            ->values();

        $copyRecipient = $copyToSuperadmin
            ? $this->resolveCopyRecipient($copyRecipient, $actor)
            : null;

        $copies = 0;

        foreach ($recipients as $recipient) {
            $report = $this->builder->build($parish, $recipient, $generatedAt);
            $mail = new ParishPriestWeeklyDigestMessage($report);

            if (filled($copyRecipient) && mb_strtolower((string) $copyRecipient) !== mb_strtolower((string) $recipient->email)) {
                $mail->cc($copyRecipient);
                $copies++;
            }

            Mail::to((string) $recipient->email)->queue($mail);
        }

        activity('parish-weekly-digests')
            ->event('parish_priest_weekly_digest_queued')
            ->causedBy($actor)
            ->performedOn($parish)
            ->withProperties([
                'generated_at' => $generatedAt->toDateTimeString(),
                'copy_to_superadmin' => $copyToSuperadmin,
                'copy_recipient' => $copyRecipient,
                'recipients' => $recipients->pluck('email')->all(),
            ])
            ->log('Zakolejkowano cotygodniowy raport parafii dla proboszcza.');

        return [
            'recipients' => $recipients->count(),
            'copies' => $copies,
            'skipped' => 0,
        ];
    }

    private function resolveCopyRecipient(?string $copyRecipient, ?User $actor): ?string
    {
        if (filled($copyRecipient)) {
            return $copyRecipient;
        }

        if ($actor instanceof User && filled($actor->email)) {
            return (string) $actor->email;
        }

        $configured = config('services.wspolnota.priest_weekly_digest_copy_recipient');

        return filled($configured) ? (string) $configured : null;
    }
}
