<?php

namespace App\Console\Commands;

use App\Models\Parish;
use App\Support\Reports\ParishPriestWeeklyDigestSender;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendParishPriestWeeklyDigestCommand extends Command
{
    protected $signature = 'parishes:send-weekly-priest-digest
        {--date= : Data raportu w formacie YYYY-MM-DD. Domyslnie teraz.}
        {--parish-id=* : Opcjonalna lista ID parafii do wysylki.}
        {--copy-to-superadmin : Wyslij kopie takze do glownego superadmina.}';

    protected $description = 'Wysyla cotygodniowa checkliste parafialna do proboszczow przypisanych do parafii.';

    public function handle(ParishPriestWeeklyDigestSender $sender): int
    {
        $dateOption = $this->option('date');

        try {
            $generatedAt = $dateOption
                ? CarbonImmutable::createFromFormat('Y-m-d', (string) $dateOption, config('app.timezone'))->setTimeFromTimeString('12:00:00')
                : CarbonImmutable::now(config('app.timezone'));
        } catch (\Throwable) {
            $this->error('Nieprawidlowy format daty. Uzyj YYYY-MM-DD.');

            return self::FAILURE;
        }

        $parishIds = collect((array) $this->option('parish-id'))
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values();

        $parishes = Parish::query()
            ->when($parishIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $parishIds->all()))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $recipients = 0;
        $copies = 0;

        foreach ($parishes as $parish) {
            $result = $sender->sendForParish(
                parish: $parish,
                generatedAt: $generatedAt,
                copyToSuperadmin: (bool) $this->option('copy-to-superadmin'),
            );

            $recipients += $result['recipients'];
            $copies += $result['copies'];
        }

        $this->info("Zakolejkowano {$recipients} maili checklisty proboszcza. Kopie: {$copies}. Parafie: {$parishes->count()}.");

        return self::SUCCESS;
    }
}
